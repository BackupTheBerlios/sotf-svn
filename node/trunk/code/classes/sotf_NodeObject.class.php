<?php 
// -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

/**
* Objects that are replicated in the network
*
* @author Andras Micsik - micsik@sztaki.hu
*/
class sotf_NodeObject extends sotf_Object {

  /** constructor
   * @return (void)
   */
  function sotf_NodeObject($tablename, $id='', $data='') {
    debug("constructor", 'sotf_NodeObject');
	 $this->sotf_Object($tablename, $id, $data);
  }						

  function generateID() {
	 $localId = $this->db->nextId($this->tablename . "_seq");
	 $id = sprintf("%03d%2s%d", $GLOBALS['nodeId'], $this->repository->getTableCode($this->tablename), $localId);
	 debug("generated ID", $id);
	 return $id;
  }

  function create() {
	 global $nodeId;
	 $this->setID($this->generateID());
	 $this->db->query("INSERT INTO sotf_node_objects ('id','node_id') VALUES('" . $this->id . "','$nodeId')");
	 return parent::create();
  }

  function update() {
	 $this->db->query("UPDATE sotf_node_objects SET last_change='" . $this->db->getTimestampTz() . "' WHERE id='" . $this->id . "'");
	 //$this->set('last_change', $this->db->getTimestampTz());
	 parent::update();
  }

  function delete() {
	 // delete access rights if any
	 //$this->db->query("DELETE FROM sotf_user_groups WHERE object_id='" . $this->id . "'");
	 $this->db->query("DELETE FROM sotf_node_objects WHERE id='" . $this->id . "'");
	 //parent::delete();  // not needed because of cascading delete
  }

  function createDeletionRecord() {
	 $dr = new sotf_NodeBase('sotf_deletions');
	 $dr->set('del_id', $this->id);
	 $dr->create();
  }

}

?>