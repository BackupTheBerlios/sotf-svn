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
    logError($msg);
    if($console)
      $page->addStatusMsg($msg);
  }

  function sync($console = false) {
    global $page;
    if(!$console && $this->get('use_for_outgoing')=='f') {
      debug("node $this->id is not used for outgoing sync");
      return;
    }
    $timestamp = $this->db->getTimestampTz();
    $remoteId = $this->get('node_id');
    $url = $this->getUrl();
    // remove trailing '/'
    while(substr($url, -1) == '/')
      $url = substr($url, 0, -1);
    // collect local data to send
    $localNode = sotf_Node::getLocalNode();
    debug("localNode", $localNode);
    $localNodeData = $localNode->getAll();
    // check if url is correct...
    $localNodeData['url'] = $rootdir;
    $objs = array($this->get('last_sync'),
                  $localNodeData,
                  sotf_NodeObject::getModifiedObjects($remoteId, $this->get('last_sync')));
    $rpc = new rpc_Utils;
    $response = $rpc->call($url . '/xmlrpcServer.php', 'sotf.sync', $objs);
    // error handling
    if(!$response) {
      $this->set('errors', $this->get('errors')+1);
      $this->update();
      return;
    }
    // save received data
    if(count($response) > 0) {
      $updatedObjects = sotf_NodeObject::saveModifiedObjects($objects);
      $this->log($console, "number of updatd objects", count($updatedObjects));
    }
    
    // save last_sync
    $this->set('success', $this->get('success')+1);
    $this->set('last_sync_out', $timestamp);
    $this->saveSyncStatus($timestamp);
    $this->update();
    // send receipt of successful sync??
  }

  function syncResponse($lastSync, $nodeData, $objects) {
    // save modified objects
    $updatedObjects = sotf_NodeObject::saveModifiedObjects($objects);
    debug("number of updatd objects", count($updatedObjects));
    $timestamp = $this->db->getTimestampTz();
    // get new objects to send as reply
    $objects = sotf_NodeObject::getModifiedObjects($this->get('node_id'), $lastSync, $updatedObjects);
    // save time of this sync
    $this->saveSyncStatus($timestamp);
    return $objects;
  }

  function saveSyncStatus($lastSync) {
    global $nodeId;
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
