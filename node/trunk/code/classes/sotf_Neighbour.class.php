<?php

class sotf_Neighbour extends sotf_Base {

  /**
   * load node data from sql
   */
  function load() {
    //fetch data from database and fill values
    return parent::load("sotf_neighbours","id");
  }

  /**
   * save node data
   */
  function save() {
    return parent::save("sotf_neighbours","id");
  }

  /**
   * delete a node
   */
  function delete(){
    return parent::delete("sotf_neighbours","id");
  }

  function listAll() {
    global $db;
    $sql = "SELECT * FROM sotf_neighbours ORDER BY id";
    $res = $db->getAll($sql);
    // if(isError)...
    foreach($res as $st) {
      $slist[] = new sotf_Neighbour($st['id'], $st);
    }
    return $slist;
  }

}

?>
