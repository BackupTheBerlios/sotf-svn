<?php 
// -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

//define('ERROR_NAME_USED', 123);

class sotf_Contact extends sotf_ComplexNodeObject {		

	 /**
		 * Constructor: loads the object from database if ids are given
		 *
		 * @param string tablename name of SQL table to store
		 * @param string node node id
		 * @param string id id within node
	 */
	function sotf_Contact($id='', $data='') {
    $this->binaryFields = array('icon', 'jingle');
		$this->sotf_ComplexNodeObject('sotf_contacts', $id, $data);
	}

  function create($name) {
    global $nodeId;
    #$name = sotf_Utils::magicQuotes(/*$nodeId . '_' . */ $name);
    #$count = $this->db->getOne("SELECT count(*) FROM sotf_contacts WHERE name = '$name'");
    #if($count > 0)
    #  return ERROR_NAME_USED;
    $this->data['name'] = $name;
    return parent::create();
  }

  function isLocal() {
    global $nodeId;
    $n = $this->db->getOne("SELECT node_id FROM sotf_node_objects WHERE id = '$this->id'");
    return ($n == $nodeId);
  }

  /** static */
  function findByName($name) {
    global $db;
    $name = sotf_Utils::magicQuotes($name);
    $res = $db->getOne("SELECT id FROM sotf_contacts WHERE name='$name'");
    // what happens when there are 2 matches? but name field is unique...
    return $res;
  }

  /** static */
  function listLocalContactNames() {
    global $db, $nodeId;
		$res = $db->getAll("SELECT c.id AS id, c.name AS name FROM sotf_contacts c, sotf_node_objects n WHERE c.id = n.id AND n.node_id='$nodeId' ORDER BY name");
		if(DB::isError($res))
      raiseError($res);
    return $res;
  }

}

?>