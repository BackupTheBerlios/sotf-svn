<?php

class sotf_Node extends sotf_Base {

  /**
   * load node data from sql
   */
  function load() {
    //fetch data from database and fill values
    return parent::load("sotf_nodes","id");
  }

  /**
   * save node data
   */
  function save() {
    $this->data['last_change'] = db_Wrap::getTimestampTz();
    return parent::save("sotf_nodes","id");
  }

  /**
   * delete a node
   */
  function delete(){
    return parent::delete("sotf_nodes","id");
  }

  function listAll() {
    global $db;
    $sql = "SELECT * FROM sotf_nodes ORDER BY id";
    $res = $db->getAll($sql);
    // if(isError)...
    foreach($res as $st) {
      $slist[] = new sotf_Node($st['id'], $st);
    }
    return $slist;
  }

  function numNodes() {
    global $db;
    return $db->getOne("SELECT count(*) FROM sotf_nodes WHERE up='t'");
  }

}

?>
