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

  function delete() {
	 global $db;
	 $db->begin();
	 sotf_NodeObject::nodeLeavingNetwork($this->get('node_id'));
	 parent::delete();
	 $db->commit();
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
	 static $list;
	 if(!$list)
		$list = $db->getCol("SELECT node_id FROM sotf_neighbours ORDER BY node_id");
	 return $list;
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
	 $more = sotf_NodeObject::countModifiedObjects($remoteId);
	 if(!$more)
		debug("No new objects to send");
	 while($more) {
		$db->begin(true);
		$modifiedObjects = sotf_NodeObject::getModifiedObjects($remoteId, 0, $this->objectsPerRPCRequest);
		$more = sotf_NodeObject::countModifiedObjects($remoteId);
		$chunkInfo = array('this_chunk' => $thisChunk,
								 'node' => $localNodeData,
								 'objects_remaining' => $more
								 );
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
	 }

	 debug("total number of objects sent",$objectsSent );
	 debug("total number of objects received",$objectsReceived );
	 //$this->log($console, "number of updated objects: " .count($updatedObjects));
	 
	 // save node and neighbour stats
	 $node = sotf_Node::getLocalNode();
	 $this->set('success', $this->get('success')+1);
	 $this->set('last_sync_out', $timestamp);
	 $node->set('last_sync_out', $timestamp);
	 // take out from pending nodes
	 if($this->get('pending_url')) {
		$this->set('pending_url','');
		$neis = sotf_Neighbour::listIds();
		$node->set('neighbours', join(',', $neis));
	 }
	 $this->update();
	 $node->update();
  }

  function syncResponse($chunkInfo, $objects) {
	 global $db;

	 $timestamp = $db->getTimestampTz();
	 $remoteId = $this->get('node_id');
	 // save modified objects
	 $db->begin(true);
	 $updatedObjects = sotf_NodeObject::saveModifiedObjects($objects, $remoteId);
	 // if db error: don't commit!
	 $db->commit();
	 debug("number of updated objects", $updatedObjects);

	 if($chunkInfo['objects_remaining'] == 0) {
		// last chunk,  save node and neighbour stats
		$node = sotf_Node::getLocalNode();
		$this->set('last_sync_in', $timestamp);
		$node->set('last_sync_in', $timestamp);
		// take out from pending nodes, update neighbour list
		if($this->get('pending_url')) {
		  $this->set('pending_url','');
		  $neis = sotf_Neighbour::listIds();
		  $node->set('neighbours', join(',', $neis));
		}
		$this->update();
		$node->update();
	 }

	 $replyInfo = array('received' => count($objects),
							  'updated' => $updatedObjects);
	 return array($replyInfo);
  }

}

?>
