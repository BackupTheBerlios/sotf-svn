<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-

/* 
 * $Id$
 *
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *				at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

/**
 * Objects that are replicated in the network
 */
class sotf_NodeObject extends sotf_Object {

  /** Contains the administrative fields needed for replication. */
  var $internalData;

  /** constructor
	* @return (void)
	*/
  function sotf_NodeObject($tablename, $id='', $data='') {
	 $this->sotf_Object($tablename, $id, $data);
  }

  /** Comfort method to retrieve an object in a cacheable way. */
  function getObject($id) {
	 global $repository;
	 return $repository->getObject($id);
  }

  /** Creates a new persistent replicated object */
  function create() {
	 global $db, $config, $repository;

	 if(empty($this->id)) {
		$this->id = $repository->generateID($this);
		$this->adminObject->id = $this->id;
	 }
	 if($repository->isVocabularyTable($this->tablename))
		$this->internalData['node_id'] = 0;
	 else
		$this->internalData['node_id'] = $config['nodeId'];
	 $this->internalData['arrived'] = $db->getTimestampTz();
	 $this->internalData['last_change'] = $db->getTimestampTz();
	 $this->internalData['change_stamp'] = 0;
	 $internalObj = new sotf_Object('sotf_node_objects', $this->id, $this->internalData);
	 $internalObj->create();
	 $success = parent::create();
	 if(!$success) {
		$internalObj->delete();
		//$db->query("DELETE FROM sotf_node_objects WHERE id='$this->id'");
	 } else {
		$this->addToRefreshTable($this->id);
	 }
	 return $success;
  }

  /** Updates the fields of the object. */
  function update() {
	 global $db, $config;

	 parent::update();
	 $this->internalData = $db->getRow("SELECT * FROM sotf_node_objects WHERE id='$this->id' ");
	 if($this->internalData['node_id'] != $config['nodeId'] && $this->internalData['node_id'] != 0 )
		logError("Updating a remote object: " . $this->id);
	 $this->internalData['arrived'] = $db->getTimestampTz();
	 $this->internalData['last_change'] = $db->getTimestampTz();
	 $this->internalData['change_stamp']++;
	 $internalObj = new sotf_Object('sotf_node_objects', $this->internalData['id'], $this->internalData);
	 $internalObj->update();
	 $this->addToRefreshTable($this->id);
  }

  /** Deletes the object. */
  function delete() {
	 global $db;

	 // TODO: don't allow to delete remote or global objects??
	 // delete administrative data about object
	 $db->query("DELETE FROM sotf_node_objects WHERE id='" . $this->id . "'");
	 // propagate deletion to other nodes
	 $this->createDeletionRecord();
	 // delete data itself: not really needed because of cascading delete
	 parent::delete();  
	 // delete user permissions
	 $db->query("DELETE FROM sotf_user_permissions WHERE object_id='$this->id'");
	 // delete replication status
	 $this->removeFromRefreshTable($this->id);
  }

  /** Creates a deletion record: used when a replicated object is deleted. */
  function createDeletionRecord() {
	 $dr = new sotf_NodeObject('sotf_deletions');
	 $dr->set('del_id', $this->id);
	 $dr->create();
  }

  /************************************************
	*		 REPLICATION STATUS MANAGEMENT
	************************************************/

  function addToRefreshTable($id, $fromNode = 0) {
	 global $db;
	 $existing = $db->getCol("SELECT node_id FROM sotf_object_status WHERE id='$id'");
	 $neighbours = sotf_Neighbour::listIds();
	 foreach($neighbours as $nei) {
		if($this->internalData['node_id'] != $nei 
			&& $nei != $fromNode 
			&& !in_array($nei, $existing)) 
		  {
			 $db->query("INSERT INTO sotf_object_status (id, node_id) VALUES('$id', $nei)");
		  }
	 }
  }
  
  /** can be static */
  function removeFromRefreshTable($id, $nodeId = 0) {
	 global $db;
	 if(!$nodeId) {
		$db->query("DELETE FROM sotf_object_status WHERE id='$id'");
	 } else {
		$db->query("DELETE FROM sotf_object_status WHERE id='$id' AND node_id='$nodeId'");
	 }
  }

  /** Static: count the objects to be sent to the neighbour node. */
  function countModifiedObjects($remoteNode) {
	 global $db;
	 return $db->getOne("SELECT count(*) FROM sotf_object_status WHERE node_id = '$remoteNode'");
  }

  /**************************************************
	*
	*					  SYNC SUPPORT
	*
	**************************************************/

  /** Private! Compares a replicated object to the local one and saves it if it's newer than the local. */
	function saveReplica() {
	  global $db;
	  
	  $db->begin();
	  $oldData = $db->getRow("SELECT * FROM sotf_node_objects WHERE id='$this->id' ");
	  //debug("changed", $changed);
	  //debug("lch", $this->lastChange);
	  if(count($oldData) > 0) {
		 if($this->internalData['change_stamp'] && $this->internalData['change_stamp'] > $oldData['change_stamp']) {
			// this is newer, save it
			sotf_Object::update();
			// save internal data
			$this->internalData['arrived'] = $db->getTimestampTz();
			$internalObj = new sotf_Object('sotf_node_objects', $this->internalData['id'], $this->internalData);
			$internalObj->update();
			// save binary fields
			/*
		  reset($this->binaryFields);
		  while(list(,$field)=each($this->binaryFields)) {
			 sotf_Object::setBlob($field, $db->unescape_bytea($this->data[$field]));
		  }
			*/
			debug("updated ", $this->id);
			$changed = true;
		 } elseif($this->internalData['change_stamp'] && $this->internalData['change_stamp'] == $oldData['change_stamp']) {
			debug("arrived same version of", $this->id);
			$changed = false;
		 } else {
			debug("arrived older version of", $this->id);
			$changed = false;
		 }
	  } else {
		 $this->internalData['arrived'] = $db->getTimestampTz();
		 $internalObj = new sotf_Object('sotf_node_objects', $this->id, $this->internalData);
		 $internalObj->create();
		 $changed = sotf_Object::create();
		 if(!$changed) {
			$internalObj->delete();
		 }
		 debug("created ", $this->id);
	  }
	  if($changed)
		 $db->commit();
	  return $changed;
	}

  /** Static: collects the objects to send to the neighbour node. */
  function getModifiedObjects($remoteNode, $from, $objectsPerPage) {
	 global $db, $config, $repository;

	 // an ordering in which objects should be retrieved because of foreign keys
	 $tableOrder = $repository->tableOrder;
	 // select objects to send to neighbour
	 $result = $db->limitQuery("SELECT no.* FROM sotf_node_objects no, sotf_object_status os WHERE no.id = os.id AND no.node_id != '$remoteNode' AND os.node_id = '$remoteNode' ORDER BY strpos('$tableOrder', substring(no.id, 4, 2)), no.id", $from, $objectsPerPage);
	 while (DB_OK === $result->fetchInto($row)) {
		$objects1[] = $row;
	 }
	 //debug("OBJECTS1", $objects1);
	 // collect object data for selected objects
	 $objects = array();
	 if(count($objects1) > 0) {
		reset($objects1);
		while(list(,$obj) = each($objects1)) {
		  $tablename = $repository->getTable($obj['id']);
		  $data = $db->getRow("SELECT * FROM $tablename WHERE id = '" . $obj['id'] . "'");
		  // don't send occasional empty records
		  if(count($data) > 1) {			  
			 $obj['data'] = $data;
			 $objects[] = $obj;
			 debug("sending modified object", $obj['id']);
		  }
		  // delete from refresh table (will roll back if failed)
		  sotf_NodeObject::removeFromRefreshTable($obj['id'], $remoteNode);
		}
	 }
	 //debug("OBJECTS__2", $objects);
	 return $objects;
  }

  /** Static: saves the objects received from a neighbour node. */
  function saveModifiedObjects($objects, $fromNode) {
	 global $repository;

	 $updatedObjects = 0;
	 if(count($objects) > 0) {
		reset($objects);
		while(list(,$objData) = each($objects)) {
		  debug("arrived object", $objData['id']);
		  $obj = $repository->getObjectNoCache($objData['id'], $objData['data']);
		  unset($objData['data']);
		  $obj->internalData = $objData;
		  /*
		  reset($obj->data);
			// url decoding and else
		  while(list($k,$v) = each($obj->data)) {
			 $obj->data[$k] = urldecode($v);
		  }
		  */
		  if($obj->saveReplica()) {
			 $updatedObjects++;
			 // handle refresh table
			 $obj->removeFromRefreshTable($obj->id, $fromNode);
			 $obj->addToRefreshTable($obj->id, $fromNode);
			 // handle deletions
			 if($obj->tablename == 'sotf_deletions') {
				$delId = $obj->get('del_id');
				debug("deleting object", $delId);
				$obj = $repository->getObjectNoCache($delId);
				if($obj)
				  $obj->delete();
			 }
		  }
		}
	 }
	 debug("number of objects updated", $updatedObjects);
	 return $updatedObjects;
  }

}

?>