<?php
require_once("$classdir/rpc_Utils.class.php");

class sotf_Neighbour extends sotf_Object {

  var $tablename = 'sotf_neighbours';

  function sotf_Neighbour($id='', $data='') {
    $this->sotf_Object('sotf_neighbours', $id, $data);
  }

	/** 
	 * @method static getById
	 */
	function getById($nodeId) {
		global $db;
		$data = $db->getRow("SELECT * FROM sotf_neighbours WHERE node_id = '$nodeId'");
    if(!$data) {
			//logError('no such neighbour: $nodeId');
      return null;
    }
		return new sotf_Neighbour($data['id'], $data);
	}

	/** 
	 * @method static isNeighbour
	 */
	function isNeighbour($nodeId) {
		global $db;
		return $db->getOne("SELECT count(*) FROM sotf_neighbours WHERE node_id = '$nodeId'");
	}

  /** returns a list of all such objects: can be slow!!
   * @method static listAll
   */
  function listAll() {
    global $db;
    $sql = "SELECT * FROM sotf_neighbours ORDER BY id";
    $res = $db->getAll($sql);
    if(DB::isError($res))
      raiseError($res);
    $slist = array();
    foreach($res as $st) {
      $slist[] = new sotf_Neighbour($st['id'], $st);
    }
    return $slist;
  }

  function getNode() {
    return sotf_Node::getNodeById($this->get('node_id'));
  }

  function getUrl() {
    $remoteNode = sotf_Node::getNodeById($this->get('node_id'));
    if($remoteNode)
      return $remoteNode->get('url');
    return $this->get('pending_url');
  }

  /** private */
  function log($console, $msg) {
    global $page;
    debug($msg);
    if($console)
      $page->addStatusMsg($msg);
  }

  function sync($console = false) {
    // tunable things
    $objectsPerRPCRequest = 200;
    
    global $page;
    if(!$console && $this->getBool('use_for_outgoing')) {
      debug("node $this->id is not used for outgoing sync");
      return;
    }
    debug("SYNCing with ", $neighbour->get("node_id"));

    $rpc = new rpc_Utils;
    $timestamp = $this->db->getTimestampTz();
    $remoteId = $this->get('node_id');
    $url = $this->getUrl();
    // remove trailing '/'
    while(substr($url, -1) == '/')
      $url = substr($url, 0, -1);
    // collect local data to send
    $localNode = sotf_Node::getLocalNode();
    //debug("localNode", $localNode);
    debug("neighbour", $this);
    $localNodeData = $localNode->getAll();
    // check if url is correct...
    $localNodeData['url'] = $rootdir;
    // calculate chunking
    $currentStamp = $sotfVars->get('sync_stamp', 0);
    $lastSyncStamp = $this->get('sync_stamp');
    $count = sotf_NodeObject::countModifiedObjects($remoteId, $lastSyncStamp);
    $numChunks = ceil($count / $objectsPerRPCRequest);
    $thisChunk = 1;
    $chunkInfo = array("old_stamp" => $lastSyncStamp,
                       "current_stamp" => $currentStamp,
                       "num_chunks" => $numChunks,
                       'this_chunk' => $thisChunk);
    // do XML-RPC conversation
    $objectsSent = 0;
    $objectsReceived = 0;
    while($thisChunk <= $numChunks) {
      if($thisChunk == $numChunks) {
        // last chunk: no limits
        $objectsPerRPCRequest = 100 * $objectsPerRPCRequest;
      }
      $modifiedObjects = sotf_NodeObject::getModifiedObjects($remoteId, $lastSyncStamp, $objectsSent+1, $objectsPerRPCRequest);
      $chunkInfo['this_chunk'] = $thisChunk;
      debug("chunk info", $chunkInfo);
      //debug("number of sent objects", count($modifiedObjects));
      $objectsSent = $objectsSent + count($modifiedObjects);
      $objs = array($chunkInfo, $localNodeData, $modifiedObjects);
      $response = $rpc->call($url . '/xmlrpcServer.php', 'sotf.sync', $objs);
      // error handling
      if(is_null($response)) {
        $this->set('errors', $this->get('errors')+1);
        $this->update();
        return;
      }
      // save received data
      $chunkInfo = $response[1];
      $newObjects = $response[2];
      $objectsReceived = $objectsReceived + count($newObjects);
      debug("number of received objects", count($newObjects));
      if(count($newObjects) > 0) {
        $updatedObjects = sotf_NodeObject::saveModifiedObjects($newObjects);
      }
      
      $thisChunk++;
      
    }
    debug("total number of objects sent",$objectsSent );
    debug("total number of objects received",$objectsReceived );
    //$this->log($console, "number of updated objects: " .count($updatedObjects));
    
    // save last_sync
    $this->set('success', $this->get('success')+1);
    $this->set('last_sync_out', $timestamp);
    $this->saveSyncStatus($timestamp, $currentStamp);
    // send receipt of successful sync??
  }

  function syncResponse($chunkInfo, $nodeData, $objects) {
    $timestamp = $this->db->getTimestampTz();
    // save modified objects
    $updatedObjects = sotf_NodeObject::saveModifiedObjects($objects);
    debug("number of updatd objects", count($updatedObjects));
    $remoteId = $this->get('node_id');
    $currentStamp = $sotfVars->get('sync_stamp', 0);
    $lastSyncStamp = $this->get('sync_stamp');
    $count = sotf_NodeObject::countModifiedObjects($remoteId, $lastSyncStamp);
    if($chunkInfo['this_chunk'] == $chunkInfo['num_chunks']) {
      // last chunk: no limits
      $objectsPerPage = 100000;
    } else {
      $objectsPerPage = ceil($count / $chunkInfo['num_chunks']);
    }
    $chunkInfo['old_stamp'] = $lastSyncStamp;
    $chunkInfo['current_stamp'] = $currentStamp;
    $from = $objectsPerPage * $chunkInfo['this_chunk'] + 1;
    debug("chunk info", $chunkInfo);
    // get new objects to send as reply
    $objects = sotf_NodeObject::getModifiedObjects($this->get('node_id'), $lastSyncStamp, $from, $objectsPerPage, $updatedObjects);
    // save time of this sync
    $this->saveSyncStatus($timestamp, $currentStamp);
    return array($chunkInfo, $objects);
  }

  function saveSyncStatus($lastSync, $syncStamp) {
    global $nodeId;
    $this->set('sync_stamp', $syncStamp);
    $this->set('last_sync', $lastSync);
    $node = $this->getNode();
    if($node) {
      $node->set('last_sync', $lastSync); //TODO: get receipt from recieving sync response??
      $node->set('authorizer', $nodeId);
      $node->update();
      if($this->get('pending_url')) {
        // take out from pending nodes
        $this->set('pending_url','');
      }
    }
    $this->update();
  }

}

?>
