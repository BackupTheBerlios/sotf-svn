<?php //-*- tab-width: 3; indent-tabs-mode: 1; -*-

class sotf_RepBase extends sotf_Base {

  /**
   * sotf::sotf()
   * 
   * purpose: constructor
   * 
   * @param	integer	$id
   * @return (void)
   */
  function sotf_RepBase($tablename, $id='', $data='') {
	 parent::constructor($tablename, $id, $data);
  }						

  function generateID() {
	 $localId = $this->db->nextId($this->tablename . "_seq");
	 $id = sprintf("%03d%2s%d", $GLOBALS['nodeId'], $this->repository->getTableCode($this->tablename), $localId);
	 debug("gernerated ID", $id);
	 return $id;
  }

  function create() {
	 $this->setID($this->generateID());
	 parent::create();
  }

  function update() {
	 $this->set('last_change', $this->db->getTimestampTz());
	 parent::update();
  }

  function delete() {
	 // delete access rights if any
	 $this->db->query("DELETE FROM sotf_user_groups WHERE object_id='" . $this->id . "'");
	 parent::delete();
  }

  function createDeletionRecord() {
	 $dr = new sotf_RepBase('sotf_deletions');
	 $dr->set('del_id', $this->id);
	 $dr->create();
  }

}

?>