<?php

/***
 * Show Class
 * purpose: to represent a SOTF SHOW :)
 * Author: Alexey Koulikov - alex@pvl.at, alex@koulikov.cc
 ************/

class sotf_Series extends sotf_Base {		
  
  /**
   * sotfShow::sotfShow()
   * 
   * el constructor
   * 
   * @param	string	$id
   * @param	object	$db_handle
   * @return (void)
   */
  function sotf_Series($id='', $data='') {
    $this->sotf_Base($id, $data);
  }

  function listSeries($station) {
    global $db;
    $slist = $db->getAll("SELECT * FROM sotf_series WHERE station='$station'");
    foreach($slist as $item) {
      $retval[] = new sotf_Series($item['id'], $item);
    }
    return $retval;
  }

  /**
   * sotfShow::populate()
   * 
   * prupose: populate the object from the database
   * @return 
   */
  function load(){
    //fetch data from database and fill values
    return parent::load("sotf_series","id");	
  }
		
  /**
   * sotfShow::commit()
   * 
   * purpose: to commit the changes made to the object with
   * 					the database.
   * @return (bool)
   */
  function save(){
    return parent::save("sotf_series","id");	
  }
  
  /**
   * sotfShow::delete()
   * 
   * purpose: to delete data from the tables
   * 
   * @return (bool)
   */
  function delete(){
    return parent::delete("sotf_series","id");
  }

  /** get number of published programmes */
  function numProgrammes($onlyPublished = true) {
    $id = $this->id;
    $station = $this->data['station'];
	$sql = "SELECT COUNT(*) FROM sotf_programmes WHERE station = '$station' AND series='$id'";
    if($onlyPublished)
      $sql .= " AND published='t'";
    $count = $this->db->getOne($sql);
    if (DB::isError($count))
      raiseError($count->getMessage());
    else
      return $count;
  }

  function listProgrammes($start, $num, $onlyPublished = true) {
    $station = $this->data['station'];
    $series = $this->id;
    $sql = "SELECT * FROM sotf_programmes WHERE station = '$station' AND series = '$series' ";
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
      $list[] = new sotf_Programme($item['id'], $item);
    }
    return $list;
  }	


}	

?>
