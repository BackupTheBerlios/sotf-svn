<?php 
// -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

/**
* Objects that are replicated in the network
*
* @author Andras Micsik - micsik@sztaki.hu
*/
class sotf_NodeObject extends sotf_Object {

  var $nodeId;
  var $lastChange;

  /** constructor
   * @return (void)
   */
  function sotf_NodeObject($tablename, $id='', $data='') {
    //debug("constructor", 'sotf_NodeObject');
    $this->sotf_Object($tablename, $id, $data);
  }						

  function generateID() {
    $localId = $this->db->nextId($this->tablename);
    $id = sprintf("%03d%2s%d", $this->nodeId, $this->repository->getTableCode($this->tablename), $localId);
    debug("generated ID", $id);
	 return $id;
  }

	function saveReplica() {
    if($this->id) {
      $changed = $this->db->getOne("SELECT last_change FROM sotf_node_object WHERE id='$this->id' ");
      if($changed) {
        if($this->lastChange && (strtotime($this->lastChange) > strtotime($changed))) {
          $this->update();
          debug("updated ", $this->id);
          return true;
        } else {
          debug("arrived older version of", $this->id);
          return false;
        }
      }
    }
		$success = $this->create();
    debug("created ", $this->id);
    return $success;
	}

  function create() {
	 global $nodeId;
   if(!$this->nodeId)
     $this->nodeId = $nodeId;
   if(!$this->lastChange)
     $this->lastChange = $this->db->getTimestampTz();
   if(empty($this->id)) {
     $this->id = $this->generateID();
   }
	 $this->db->query("INSERT INTO sotf_node_objects (id, node_id, last_change) VALUES('$this->id','$this->nodeId', '$this->lastChange')");
	 return parent::create();
  }

  function update() {
	 parent::update();
   if(!$this->lastChange)
     $this->lastChange = $this->db->getTimestampTz();
	 $this->db->query("UPDATE sotf_node_objects SET last_change='$this->lastChange' WHERE id='" . $this->id . "'");
  }

  function delete() {
	 $this->db->query("DELETE FROM sotf_node_objects WHERE id='" . $this->id . "'");
   // propagate deletion to other nodes
   $this->createDeletionRecord();
	 //parent::delete();  // not needed because of cascading delete
   // delete user permissions
   $this->db->query("DELETE FROM sotf_user_permissions WHERE object_id='$this->id'");
  }

	function setBlob($prop_name, $prop_value) {
    parent::setBlob($prop_name, $prop_value);
    $this->db->query("UPDATE sotf_node_objects SET last_change='" . $this->db->getTimestampTz() . "' WHERE id='" . $this->id . "'");
  }

  function createDeletionRecord() {
	 $dr = new sotf_NodeObject('sotf_deletions');
	 $dr->set('del_id', $this->id);
	 $dr->create();
  }

  // **** sync support

  /** static */
  function getModifiedObjects($remoteNode, $date='', $updatedObjects = array()) {
    global $db, $nodeId, $repository;
    if($date)
      $whereClause .= "AND arrived >= '$date'";
    $objects1 = $db->getAll("SELECT * FROM sotf_node_objects WHERE node_id != '$remoteNode' $dateClause ORDER BY substring(id, 4, 2), id");
    //debug("OBJECTS__1", $objects);
    $objects = array();
    while(list(,$obj) = each($objects1)) {
      if(!in_array($obj['id'], $updatedObjects)) {   // don't send back the same object
        $tablename = $repository->getTable($obj['id']);
        $data = $db->getRow("SELECT * FROM $tablename WHERE id = '" . $obj['id'] . "'");
        if(count($data) > 1) {         // don't send occasional empty records
          $obj['data'] = $data;
          $objects[] = $obj;
        }
      }
    }
    //debug("OBJECTS__2", $objects);
    return $objects;
  }

  /** static */
  function saveModifiedObjects($objects) {
    global $repository;
    $updatedObjects = array();
    reset($objects);
    while(list(,$objData) = each($objects)) {
      debug("saving modified object", $objData['id']);
      $tablename = $repository->getTable($objData['id']);
      $obj = new sotf_NodeObject($tablename, $objData['id'], $objData['data']);
      $obj->lastChange = $objData['last_change'];
      $obj->nodeId = $objData['node_id'];
      if($obj->saveReplica()) {
        $updatedObjects[] = $objData['id'];
        // handle deletions
        if($tablename == 'sotf_deletions') {
          $delId = $obj->get('del_id');
          debug("deleting object", $delId);
          $obj = $repository->getObject($delId);
          $obj->delete();
        }
      }
    }
    return $updatedObjects;
  }
  

}

?>