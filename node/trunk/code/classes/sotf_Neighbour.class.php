<?php

class sotf_Neighbour extends sotf_Object {

  var $tablename = 'sotf_neighbours';

  function sotf_Neighbour($id='', $data='') {
    parent::constructor('sotf_neighbours', $id, $data);
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
    foreach($res as $st) {
      $slist[] = new sotf_Neighbour($st['id'], $st);
    }
    return $slist;
  }

}

?>
