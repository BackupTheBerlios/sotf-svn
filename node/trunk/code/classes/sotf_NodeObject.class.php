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
  function &getObject($id) {
	 global $repository;
	 return $repository->getObject($id);
  }

  /** Tells if the given object id is for one of the global controlled vocabularies (roles, genres, topics). */
  function isVocabularyTable($tablename) {
	 global $repository;
    $tc = $repository->getTableCode($tablename);
    //debug('tc', $tc);
    if($tc == 'tt' || $tc == 'td' || $tc == 'to' || $tc == 'ge' || $tc == 'ro' || $tc == 'rn') {
      //debug("vocabulary entry");
      return true;
    } else {
      return false;
    }
  }

  function makeId($nodeId, $tablename, $serial) {
	 global $repository;
    $tableCode = $repository->getTableCode($tablename);
    //if($this->isVocabularyTable($tablename)) 
    //  $nodeId = 0;
	 if(is_numeric($serial))
		return sprintf("%03d%2s%d", $nodeId, $tableCode, $serial);
	 else
		return sprintf("%03d%2s%s", $nodeId, $tableCode, $serial);
  }

  /** Generates the ID for a new persistent object. */
  function generateID() {
    global $config, $db;
    if($config['nodeId'] == 0)
      raiseError('Please set config[nodeId] to a positive integer in config.inc.php');
    $localId = $db->nextId($this->tablename);
	 $id = $this->makeId($config['nodeId'], $this->tablename, $localId);
    debug("generated ID", $id);
    return $id;
  }

  /** Creates a new persistent replicated object */
  function create() {
	 global $db, $config, $repository;

	 if(empty($this->id)) {
		$this->id = $this->generateID();
		//$this->internalData['id'] = $this->id;
	 }
	 if($this->isVocabularyTable($this->tablename))
		$this->internalData['node_id'] = '0';
	 else
		$this->internalData['node_id'] = $config['nodeId'];
	 $this->internalData['arrived'] = $db->getTimestampTz();
	 $this->internalData['last_change'] = $db->getTimestampTz();
	 $this->internalData['change_stamp'] = '0';
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
	 if($this->isLocal())
		$this->createDeletionRecord();
	 // delete data itself: not really needed because of cascading delete
	 parent::delete();  
	 // delete user permissions
	 $db->query("DELETE FROM sotf_user_permissions WHERE object_id='$this->id'");
	 // delete replication status
	 $this->removeFromRefreshTable($this->id);
	 // delete from to_update table
	 $db->query("DELETE FROM sotf_to_update WHERE row_id='$this->id'");
  }

  /** Creates a deletion record: used when a replicated object is deleted. */
  function createDeletionRecord() {
	 $dr = new sotf_NodeObject('sotf_deletions');
	 $dr->set('del_id', $this->id);
	 $dr->create();
  }

  /** static: tells if we have objects from the given node */
  function hasObjects($nodeId) {
	 global $db;
	 return $db->getOne("SELECT count(*) FROM sotf_node_objects WHERE node_id = '$nodeId'");
  }

  function loadInternalData() {
	 global $db;
	 $this->internalData = $db->getRow("SELECT * FROM sotf_node_objects WHERE id='$this->id' ");
  }

  function getNodeId() {
	 if(count($this->internalData)==0)
		$this->loadInternalData();
	 return  $this->internalData['node_id'];
  }

  function isLocal() {
	 global $config;
	 $retval = ($this->getNodeId()==$config['nodeId']);
	 debug("isLocal1", $retval);
	 return $retval;
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
  function newNodeInNetwork($newId) {
	 global $db;
	 $db->begin();
	 $count = $db->getOne("SELECT count(*) FROM sotf_object_status WHERE node_id = '$newId'");
	 if($count > 0)
		raiseError("THis new node is not new at all: $newId");
	 $db->query("INSERT INTO sotf_object_status SELECT id, '$newId' AS node_id FROM sotf_node_objects WHERE node_id != '$newId' OR node_id IS NULL");
	 $db->commit();
  }
 
  /** can be static */
  function nodeLeavingNetwork($nodeId) {
	 global $db;
	 $db->query("DELETE FROM sotf_object_status WHERE node_id='$nodeId'");
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

  /**************************************************
	*
	*					  SYNC SUPPORT
	*
	**************************************************/

  /** Private! Compares a replicated object to the local one and saves it if it's newer than the local. */
	function saveReplica($fromNode) {
	  global $db, $repository;
	  
	  $oldData = $db->getRow("SELECT * FROM sotf_node_objects WHERE id='$this->id' ");
	  //debug("changed", $changed);
	  //debug("lch", $this->lastChange);
	  if(count($oldData) > 0) {
		 if($this->internalData['change_stamp'] && $this->internalData['change_stamp'] > $oldData['change_stamp']) {
			// this is newer, save it
			debug("arrived newer version of", $this->id);
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
			$this->addToRefreshTable($this->id, $fromNode);
			$this->removeFromRefreshTable($this->id, $fromNode);
			$changed = true;
		 } elseif($this->internalData['change_stamp'] == $oldData['change_stamp']) {
			debug("arrived same version of", $this->id);
			$this->removeFromRefreshTable($this->id, $fromNode);
			$changed = false;
		 } else {
			debug("arrived older version of", $this->id);
			$changed = false;
		 }
	  } else {
		 debug("arrived new object", $this->id);
		 $this->internalData['arrived'] = $db->getTimestampTz();
		 $internalObj = new sotf_Object('sotf_node_objects', $this->id, $this->internalData);
		 $internalObj->create();
		 $changed = sotf_Object::create();
		 if(!$changed) {
			//	$internalObj->delete();
			logError("Could not create object: " . $this->id);
		 } else {
			debug("created ", $this->id);
			$this->addToRefreshTable($this->id, $fromNode);
			$this->removeFromRefreshTable($this->id, $fromNode);
		 }
	  }
	  // handle deletions
	  if($changed && $this->tablename == 'sotf_deletions') {
		 $delId = $this->get('del_id');
		 debug("deleting object", $delId);
		 $obj = $repository->getObjectNoCache($delId);
		 if($obj)
			$obj->delete();
	  }
	  return $changed;
	}

  /** Static: count the objects to be sent to the neighbour node. */
  function countModifiedObjects($remoteNode) {
	 global $db;
	 return $db->getOne("SELECT count(*) FROM sotf_object_status WHERE node_id = '$remoteNode'");
  }

  /** Static: collects the objects to send to the neighbour node. */
  function getModifiedObjects($remoteNode, $objectsPerPage) {
	 global $db, $config, $repository;

	 // an ordering in which objects should be retrieved because of foreign keys
	 $tableOrder = $repository->tableOrder;
	 // select objects to send to neighbour
	 $result = $db->limitQuery("SELECT no.* FROM sotf_node_objects no, sotf_object_status os WHERE no.id = os.id AND no.node_id != '$remoteNode' AND os.node_id = '$remoteNode' ORDER BY no.last_change, no.id", 0, $objectsPerPage);
	 // was: ORDER BY strpos('$tableOrder', substring(no.id, 4, 2)), no.id"
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
			 debug("", $obj);
			 //if(is_array($data)) foreach($data as $k => $v) {if(is_null($v)) debug($k, "is_null"); if($v === NULL) debug($k, "is null"); }
			 if($tablename == 'sotf_blobs') 
				$countBlobs++;
		  }
		  // delete from refresh table (will roll back if failed)
		  sotf_NodeObject::removeFromRefreshTable($obj['id'], $remoteNode);
		  // we cannot send too many blobs, it will result in memory allocation problems on the other side (but why??)
		  if($countBlobs >= 5)
			 break;
		}
	 }
	 //debug("OBJECTS__2", $objects);
	 debug("sending ". count($objects) . " objects");
	 return $objects;
  }

  /** Static: saves the objects received from a neighbour node. */
  function saveModifiedObjects($objects, $fromNode) {
	 global $repository, $config;

	 $updatedObjects = 0;
	 if(count($objects) > 0) {
		reset($objects);
		while(list(,$objData) = each($objects)) {

		  debug("arrived object", $objData['id']);
		  debug("", $objData);
		  $data = $objData['data'];
		  //if(is_array($data)) foreach($data as $k => $v) {if(is_null($v)) debug($k, "is_null"); if($v === NULL) debug($k, "is null"); }
		  $obj = $repository->getObjectNoCache($objData['id'], $objData['data']);
		  unset($objData['data']);
		  $obj->internalData = $objData;

		  // handle NULL problems
		  reset($obj->data);
		  while(list($k,$v) = each($obj->data)) {
			 if($v == NULL) {
				//debug("nulled", $k);
				$obj->data[$k] = NULL;
			 }
		  }
		  //debug("after nulling", $obj->data);

		  // save object
		  if($objData['node_id'] == $config['nodeId']) {
			 logError("Received my own object back via replication: ". $objData['id']);
		  } else {
			 if($obj->saveReplica($fromNode))
				$updatedObjects++;
		  }
		}
	 }
	 debug("number of objects updated", $updatedObjects);
	 return $updatedObjects;
  }

  /**************************************************
	*
	*					  MESSAGE FORWARD SUPPORT
	*
	**************************************************/

	/** may be static, if all paramters filled -- When you have to send forward stats data to the home node */
  function createForwardObject($type, $data, $objId=0, $nodeId=0) {
	 global $db;
	 if(!$objId)
		$objId = $this->id; // the id of the target object
	 if(!$nodeId)
		$nodeId = $this->getNodeId(); // the id of the node to send this to
	 $obj = new sotf_Object("sotf_to_forward");
	 $obj->setAll(array('prog_id' => $objId,
							  'node_id' => $nodeId,
							  'type' => $type,
							  'entered' => $db->getTimestampTz(),
							  'data' => serialize($data)
					  ));
	 $obj->create();
  }

  /** Static: count the objects to be sent to the node. */
  function countForwardObjects($remoteNode) {
	 global $db;
	 return $db->getOne("SELECT count(*) FROM sotf_to_forward WHERE node_id = '$remoteNode'");
  }

  /** Static: collects the objects to forward to node. */
  function getForwardObjects($remoteNode, $objectsPerPage) {
	 global $db, $config, $repository;

	 // select objects to send to neighbour
	 $result = $db->limitQuery("SELECT * FROM sotf_to_forward WHERE node_id = '$remoteNode' ORDER BY entered", 0, $objectsPerPage);
	 while (DB_OK === $result->fetchInto($row)) {
		$objects[] = $row;
	 }
	 if(count($objects) > 0) {
		for($i=0; $i<count($objects); $i++) {
		  // unserialize data object
		  $objects[$i]['data'] = unserialize($objects[$i]['data']);
		  // debug("sending forward object", $objects[$i]);
		  // delete from forward table (will roll back if failed)
		  $db->query("DELETE FROM sotf_to_forward WHERE id='". $objects[$i]['id'] . "'");
		}
	 }
	 //debug("OBJECTS", $objects);
	 debug("forwarding ". count($objects) . " objects");
	 return $objects;
  }

  /** static: applies changes suggested by objects forwarded from other nodes. */
  function saveForwardObjects($objects) {
	 global $repository;
	 if(count($objects) > 0) {
		reset($objects);
		while(list(,$obj) = each($objects)) {
		  debug("saving forward object", $obj);
		  $data = $obj['data'];
		  switch($obj['type']) {
		  case 'stat':
			 sotf_Statistics::addRemoteStat($data);
			 $count++;
			 break;
		  case 'rating':
			 $rating = new sotf_Rating();
			 $rating->setRemoteRating($data);
			 $count++;
			 break;
		  case 'event':
			 $repository->processPortalEvent($data);
			 break;
		  default:
			 logError("Unknown forward object type: " . $obj['type']);
		  }
		}
	 }
	 return $count;
  }

}

?>