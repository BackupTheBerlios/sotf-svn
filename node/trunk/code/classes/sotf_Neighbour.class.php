<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-

/* 
 * $Id$
 *
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *				at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require_once($config['classdir'] . "/rpc_Utils.class.php");

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

  /** static */
  function listIds() {
	 global $db;
	 return $db->getCol("SELECT node_id FROM sotf_neighbours ORDER BY node_id");
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

  var $objectsPerRPCRequest = 30;

  function sync($console = false) {
	global $db;

	 global $page;
	 if(!$console && $this->getBool('use_for_outgoing')) {
		debug("node $this->id is not used for outgoing sync");
		return;
	 }
	 debug("SYNCing with ", $this->get("node_id"));

	 $rpc = new rpc_Utils;
	 if($config['debug'])
		$rpc->debug = true;
	 $timestamp = $db->getTimestampTz();
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
	 $localNodeData['url'] = $config['rootUrl'];
	 // calculate chunking
	 $thisChunk = 1;
	 // do XML-RPC conversation
	 $objectsSent = 0;
	 $objectsReceived = 0;
	 $more = true;
	 while($more) {
		$db->begin();
		$modifiedObjects = sotf_NodeObject::getModifiedObjects($remoteId, 0, $this->objectsPerRPCRequest);
		$chunkInfo = array('this_chunk' => $thisChunk,
								 'node' => $localNodeData);
		debug("chunk info", $chunkInfo);
		//debug("number of sent objects", count($modifiedObjects));
		//$objectsSent = $objectsSent + count($modifiedObjects);
		$objs = array($chunkInfo, $modifiedObjects);
		$response = $rpc->call($url . '/xmlrpcServer.php', 'sotf.sync', $objs);
		// error handling
		if(is_null($response)) {
		  $this->set('errors', $this->get('errors')+1);
		  $this->update();
		  $db->rollback();
		  return;
		}
		$db->commit();
		// save received data
		$replyInfo = $response[0];
		debug("replyInfo", $replyInfo);
		$thisChunk++;
		$more = (sotf_NodeObject::countModifiedObjects($remoteId) > 0);
	 }

	 debug("total number of objects sent",$objectsSent );
	 debug("total number of objects received",$objectsReceived );
	 //$this->log($console, "number of updated objects: " .count($updatedObjects));
	 
	 // save last_sync
	 $this->set('success', $this->get('success')+1);
	 $this->set('last_sync_out', $timestamp);
	 /*
	 // take out from pending nodes
	 if($this->get('pending_url')) {
		$this->set('pending_url','');
	 }
	 */
	 $this->update();
	 $this->saveNodeStatus($timestamp);
	 // send receipt of successful sync??
  }

  function syncResponse($chunkInfo, $objects) {
	 global $db;

	 $timestamp = $db->getTimestampTz();
	 // save modified objects
	 $db->begin();
	 $updatedObjects = sotf_NodeObject::saveModifiedObjects($objects);
	 // if db error: don't commit!
	 $db->commit();
	 debug("number of updatd objects", $updatedObjects);
	 //$remoteId = $this->get('node_id');
	 // save time of this sync
	 $this->set('last_sync_in', $timestamp);
	 // take out from pending nodes
	 if($this->get('pending_url')) {
		$this->set('pending_url','');
	 }
	 $this->update();
	 $this->saveNodeStatus($timestamp, $currentStamp);
	 $replyInfo = array('received' => count($objects),
							  'updated' => $updatedObjects);
	 return array($replyInfo);
  }

  function saveNodeStatus($lastSync) {
	 global $config;
	 $node = $this->getNode();
	 if($node) {
		$node->set('last_sync', $lastSync); //TODO: get receipt from recieving sync response??
		$node->set('authorizer', $config['nodeId']);
		$node->update();
	 }
  }

}

?>
