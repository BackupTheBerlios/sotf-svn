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
    global $nodeId;
    $localId = $this->db->nextId($this->tablename);
    $id = sprintf("%03d%2s%d", $nodeId, $this->repository->getTableCode($this->tablename), $localId);
    debug("generated ID", $id);
	 return $id;
  }

	function saveReplica() {
    if($this->id) {
      $exists = $this->db->getOne("SELECT count(*) FROM " . $this->tablename . " WHERE " . $this->idKey . "='" . $this->id . "' ");
      $changed = $this->db->getOne("SELECT last_change FROM sotf_node_object WHERE id='$this->id' ");
      if($exists) {
        if($this->lastChange && (strtotime($this->lastChange) > strtotime($changed))) {
          $this->update();
          debug("updated ", $this->id);
        } else {
          debug("arrived older version of", $this->id);
        }
        return;
      }
    }
		$this->create();
    debug("created ", $this->id);
	}

  function create() {
	 global $nodeId;
   if(empty($this->id)) {
     $this->id = $this->generateID();
   }
   if(!$this->nodeId)
     $this->nodeId = $nodeId;
   if(!$this->lastChange)
     $this->lastChange = $this->db->getTimestampTz();
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
  function getModifiedObjects($date, $localOnly = true) {
    global $db, $nodeId, $repository;
    if($localOnly)
      $whereClause = "node_id='$nodeId'";
    if($date && $localOnly)
      $whereClause .= " AND ";
    if($date)
      $whereClause .= "last_change >= '$date'";
    $objects1 = $db->getAll("SELECT * FROM sotf_node_objects WHERE $whereClause ORDER BY substring(id, 4, 2), id");
    //debug("OBJECTS__1", $objects);
    $objects = array();
    while(list(,$obj) = each($objects1)) {
      $tablename = $repository->getTable($obj['id']);
      $data = $db->getRow("SELECT * FROM $tablename WHERE id = '" . $obj['id'] . "'");
      if(count($data) > 1) {         // don't send occasional empty records
        $obj['data'] = $data;
        $objects[] = $obj;
      }
    }
    //debug("OBJECTS__2", $objects);
    return $objects;
  }

  /** static */
  function saveModifiedObjects($objects) {
    global $repository;
    reset($objects);
    while(list(,$objData) = each($objects)) {
      debug("saving modified object", $objData['id']);
      $tablename = $repository->getTable($objData['id']);
      $obj = new sotf_NodeObject($tablename, $objData['id'], $objData['data']);
      $obj->saveReplica();
      // handle deletions
      if($tablename == 'sotf_deletions') {
        $delId = $obj->get('del_id');
        debug("deleting object", $delId);
        $obj = $repository->getObject($delId);
        $obj->delete();
      }
    }
  }
  

}

?>