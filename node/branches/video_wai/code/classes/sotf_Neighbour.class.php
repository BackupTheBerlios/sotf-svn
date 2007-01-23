<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-

/* 
 * $Id: sotf_Neighbour.class.php 376 2005-04-06 14:55:12Z micsik $
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

  var $objectsPerRPCRequest = 20;

  function sync($console = false) {
	 global $db, $page, $config;

	 $remoteId = $this->get('node_id');
	 if(!$console && $this->get('use_for_outgoing') != 't') {
		debug("node $remoteId is not used for outgoing sync");
		return;
	 }
	 debug("SYNCing with ", $this->get("node_id"));

	 $rpc = new rpc_Utils;
	 if($config['debug'])
		$rpc->debug = true;
	 $timestamp = $db->getTimestampTz();
	 $url = $this->getUrl();
	 // remove trailing '/'
	 while(substr($url, -1) == '/')
		$url = substr($url, 0, -1);
	 // collect local data to send
	 $localNode = sotf_Node::getLocalNode();
	 // check if url is correct...
	 if($localNode->get('url') != $config['rootUrl']) {
		$localNode->set('url', $config['rootUrl']);
		$localNode->update();
	 }
	 //debug("localNode", $localNode);
	 //debug("neighbour", $this);
	 $localNodeData = $localNode->getAll();
	 // calculate chunking
	 $thisChunk = 1;
	 // do XML-RPC conversation
	 $objectsSent = 0;
	 $more = sotf_NodeObject::countModifiedObjects($remoteId);
	 if(!$more)
		debug("No new objects to send");
	 while($more) {
		$db->begin(true);
		$modifiedObjects = sotf_NodeObject::getModifiedObjects($remoteId, $this->objectsPerRPCRequest);
		$remaining = sotf_NodeObject::countModifiedObjects($remoteId);
		if(count($modifiedObjects)==0 && $remaining > 0) {
		  logError("DATA integrity problem", "$remaining objects remained in sotf_object_status after sync");
		}
		if($remaining==0 || count($modifiedObjects)==0)
		  $more = false;
		else
		  $more = true;
		$chunkInfo = array('this_chunk' => $thisChunk,
								 'node' => $localNodeData,
								 'objects_remaining' => $remaining
								 );
		debug("chunk info", $chunkInfo);
		debug("number of sent objects", count($modifiedObjects));
		$objectsSent = $objectsSent + count($modifiedObjects);
		$objs = array($chunkInfo, $modifiedObjects);
		$response = $rpc->call($url . "/xmlrpcServer.php/sync/$thisChunk", 'sotf.sync', $objs);
		// error handling
		$replyInfo = $response[0];
		debug("replyInfo", $replyInfo);
		if(is_null($response) || $replyInfo['error']) {
		  $this->set('errors', $this->get('errors')+1);
		  $this->update();
		  $db->rollback();
		  return;
		}
		$db->commit();
		// save received data
		$thisChunk++;
	 }

	 debug("total number of objects sent",$objectsSent );
	 //$this->log($console, "number of updated objects: " .count($updatedObjects));
	 
	 // save node and neighbour stats
	 if($error) {
		$this->set('errors', $this->get('errors')+1);
	 } else {
		$this->set('success', $this->get('success')+1);
	 }
	 $this->set('last_sync_out', $timestamp);
	 $localNode->set('last_sync_out', $timestamp);
	 // take out from pending nodes
	 if($this->get('pending_url')) {
		$remoteNode = sotf_Node::getNodeById($remoteId);
		// TODO: problem is that if this is first sync or one-way connection, then object fro remote node may not exist
		if($remoteNode) {
		  $this->set('pending_url','');
		}
		$localNode->set('neighbours', $this->getNeighbourString());
	 }
	 $this->update();
	 $localNode->update();
  }

  function syncResponse($chunkInfo, $objects) {
	 global $db;

	 if($this->get('accept_incoming') != 't') {
		debug("node $remoteId is not allowed for incoming sync!");
		return NULL;
	 }
	 $timestamp = $db->getTimestampTz();
	 $remoteId = $this->get('node_id');
	 // save modified objects
	 $db->begin(true);
	 // TODO: itt gaz van! ha nem sikerul egy objektumot elmenteni, akkor soha tobbe nem lesz neki elkuldve!!!
	 $updatedObjects = sotf_NodeObject::saveModifiedObjects($objects, $remoteId);
	 // if db error: don't commit!
	 if(is_null($updatedObjects))
		return array(array('error' => "store object failed, sync aborted"));
	 $db->commit();
	 debug("number of updated objects", $updatedObjects);
	 $replyInfo = array('received' => count($objects),
							  'updated' => $updatedObjects);

	 if($chunkInfo['objects_remaining'] == 0) {
		// last chunk,  save node and neighbour stats
		$node = sotf_Node::getLocalNode();
		$this->set('last_sync_in', $timestamp);
		$node->set('last_sync_in', $timestamp);
		// take out from pending nodes, update neighbour list
		if($this->get('pending_url')) {
		  $this->set('pending_url','');
		  $node->set('neighbours', $this->getNeighbourString());
		}
		$this->update();
		$node->update();
		//$replyInfo['node'] = $node->getAll();
	 }
	 return array($replyInfo);
  }

  function getNeighbourString() {
	 $neis = sotf_Neighbour::listAll();
	 $first = 1;
	 while(list(,$nei) = each($neis)) {
		if($first)
		  $first = 0;
		else
		  $retval .= ',';
		$retval .= $nei->get('node_id');
		if($nei->getBool('accept_incoming'))
		  $retval .= 'i';
		if($nei->getBool('use_for_outgoing'))
		  $retval .= 'o';
	 }
	 return $retval;
  }

}

?>
