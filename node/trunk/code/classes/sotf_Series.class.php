<?php
// -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

/**
* Models a series
*
* @author Andras Micsik SZTAKI DSD micsik@sztaki.hu
*/
class sotf_Series extends sotf_ComplexNodeObject {		

  var $roles;

  var $access;

   /**
     * Constructor: loads the object from database if ids are given
     *
     * @param string tablename name of SQL table to store
     * @param string node node id
     * @param string id id within node
   */
  function sotf_Series($id='', $data='') {
    $this->binaryFields = array('icon', 'jingle');
    $this->sotf_ComplexNodeObject('sotf_series', $id, $data);
    if($id) {
      //$this->stationName = $this->db->getOne("SELECT name FROM sotf_stations WHERE id='" . $this->get('station_id') . "'");
    }
  }

  /** get number of published programmes */
  function numProgrammes($onlyPublished = true) {
    $node = $this->node;
    $id = $this->id;
    $station = $this->data['station'];
    $sql = "SELECT COUNT(*) FROM sotf_programmes WHERE node_id = '$node' AND series_id='$id'";
    if($onlyPublished)
      $sql .= " AND published='t'";
    $count = $this->db->getOne($sql);
    if (DB::isError($count))
      raiseError($count->getMessage());
    else
      return $count;
  }

  function listProgrammes($start, $num, $onlyPublished = true) {
    $node = $this->node;
    $id = $this->id;
    $sql = "SELECT node_id, id FROM sotf_programmes WHERE node_id = '$node' AND series_id='$id'";
    if($onlyPublished)
      $sql .= " AND published='t' ";
    $sql .= " ORDER BY entry_date DESC,track ASC";
    if ($num) {
      if ($num < 0)
        $num = 0;
      $sql .= " LIMIT $num OFFSET $start";
    }
    $res = $this->db->getAll($sql);
    if(DB::isError($res))
      raiseError($res->getMessage());
    foreach($res as $item) {
      $list[] = new sotf_Programme($item['node_id'], $item['id']);
    }
    return $list;
  }


}	

?>
