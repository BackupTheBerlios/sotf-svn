<?php //-*- tab-width: 3; indent-tabs-mode: 1; -*-

/****
 * Parent class for Classes sotfShow, sotfStation, sotfSeries
 * Purpose: An interface for subclasses
 * Author: Kulikov Alexey - alex@pvl.at, alex@koulikov.cc
 * Date. 24.07.2002
 *****************/
class sotf_Base {

  /** the unique ID of the object */
  var $id;
  /** all the properties of the object */
  var $data = array();
  /** the repository to which this object belongs */
  var $repository;
  /** the db handle */
  var $db;
  /** if it needs saving */
  var $changed;

  /**
   * sotf::sotf()
   * 
   * purpose: constructor
   * 
   * @param $id (int)
   * @return (void)
   */
  function sotf_Base($id='', $data=''){
    global $repository;
    $this->id = $id;
    $this->repository=$repository;
    $this->db = $repository->db;
    $this->changed = false;
    if(is_array($data)) {
      $this->data = $data;
    } else {
      if($id)
        $this->load();
    }
  }						

	/**
	* Generic SQL utility: can be called as sotf_Base::setData. Inserts or updates a row in the table.
	*
	* @param	string	$table        name of table
	* @param	array   $data         assoc. array of db fields and values
	* @param	string	$primaryKey   primary key
	* @access	public
	*/
function setData($table, $data, $primaryKey) {
  global $db;
  $db = getDBConnection();
  $exists = $db->getOne("SELECT count(*) FROM $table WHERE $primaryKey='" . $data[$primaryKey] . "'");
  if(!$exists) {
    foreach($data as $key => $value) {
      if($value) {
	$value = addslashes(stripslashes($value));
	$sql1 .= "$key, ";
	$sql2 .= " '$value', ";
      }
    }
    $sql1 = substr($sql1, 0, strlen($sql1)-2); // chop trailing comma
    $sql2 = substr($sql2, 0, strlen($sql2)-2); // chop trailing comma
    $sql = "INSERT INTO $table ( $sql1 ) VALUES ( $sql2 )";
    $res = $db->query($sql);
    if(DB::isError($res)) {
      die ($res->getMessage());
      //return $res;
    }
  } else {
    // need UPDATE instead of INSERT
    $sql = "UPDATE $table SET ";
    foreach($data as $key => $value) {
      if($value && ($primaryKey != $key) )
	$value = addslashes(stripslashes($value));
	$sql .= " $key='$value', ";
    }
    $sql = substr($sql, 0, strlen($sql)-2); // chop trailing comma
    $sql = $sql . " WHERE $primaryKey='" . $data[$primaryKey] . "'";
    $res = $db->query($sql);
    if(DB::isError($res)) {
      die ($res->getMessage());
      //return $res;
    }
    $updated = true;
  }
  return $updated;
}

	/**
	* Generic SQL utility: can be called as sotf_Base::setData. Inserts or updates a row in the table.
	*
	* @param	string	$table        name of table
	* @param	array   $data         assoc. array of db fields and values
	* @param	string	$primaryKey   primary key
	* @param	string	$dateField    updates only if this field contains older timestamp in db
	* @access	public
	*/
function setDataIfNewer($table, $data, $primaryKey, $dateField) {
  global $db;
  $existing = $db->getRow("SELECT * FROM $table WHERE $primaryKey='" . $data[$primaryKey] . "'");
  if(empty($existing)) {
    foreach($data as $key => $value) {
      if($value) {
	$value = addslashes(stripslashes($value));
	$sql1 .= "$key, ";
	$sql2 .= " '$value', ";
      }
    }
    $sql1 = substr($sql1, 0, strlen($sql1)-2); // chop trailing comma
    $sql2 = substr($sql2, 0, strlen($sql2)-2); // chop trailing comma
    $sql = "INSERT INTO $table ( $sql1 ) VALUES ( $sql2 )";
    $res = $db->query($sql);
    if(DB::isError($res)) {
      die ($res->getMessage());
      //return $res;
    }
    $success = true;
  } else {
    // need UPDATE instead of INSERT
    if(empty($data[$dateField])) {  // can be reomved later...
      warning("$dateField empty in table $table for " . $data[$primaryKey]);
      return false;
    }
    if(empty($existing[$dateField]) || strtotime($data[$dateField]) > strtotime($existing[$dateField])) {  
      $sql = "UPDATE $table SET ";
      foreach($data as $key => $value) {
	if($value && ($primaryKey != $key) )
	  $value = addslashes(stripslashes($value));
	  $sql .= " $key='$value', ";
      }
      $sql = substr($sql, 0, strlen($sql)-2); // chop trailing comma
      $sql = $sql . " WHERE $primaryKey='" . $data[$primaryKey] . "'";
      $res = $db->query($sql);
      if(DB::isError($res)) {
	die ($res->getMessage());
	//return $res;
      }
      $success = true;
    }
  }
  return $success;
}

	/**
	* Generic SQL utility: can be called as sotf_Base::setDataWithId. Inserts or updates a row in the table.
	*
	* @param	string	$table        name of table
	* @param	string	$idKey        name of primary key
	* @param	string	$table        value of primary key
	* @param	array   $data         assoc. array of db fields and values
	* @access	public
	*/
  function saveDataWithId($table, $idKey, $id, $data) {
    $data[$idKey] = $id;
    //check if the object has changed from its initial state
    if($this->changed){
      //UPDATE or INSERT?
      $res = $this->db->getOne("SELECT count(*) AS tot FROM " . $table . " WHERE " . $idKey . " = '" . $id . "'");
      
      if($res>0){ //UPDATE!
	//create a small portion of SQL for Update Statement based on the keys
	while(list($key,$val)=each($data)){
	  if($key!=$idKey){
	    if($val === NULL){
	      $my_sql[] = $key . " = NULL";
	    }else{
	      $my_sql[] = $key . " = '" . $val . "'";
	    }
	  }
	}
	$my_sql = implode(", ", $my_sql);
	
	//execute the query
	$res = $this->db->query("UPDATE " . $table . " SET " . $my_sql . " WHERE " . $idKey . " = '" . $id . "'");
	
	//if the query is dead, stop executio, output error
	if(DB::isError($res)){
	  trigger_error($res->getMessage());
	}
      }else{	//INSERT!!!
	while(list($key,$val)=each($data)){
	  $keys[] = $key;
	  if($val === NULL){
	    $values[] = "NULL";
	  }else{
	    $values[] = "'" . $val . "'";
	  }
	}
	$keys = implode(",",$keys);
	$values = implode(",",$values);
	
	//execure query
	$res = $this->db->query("INSERT INTO " . $table . "(" . $keys . ") VALUES(" . $values . ")");
	
	//if the query is dead, stop executio, output error
	if(DB::isError($res)){
	  raiseError($res->getMessage());
    exit;
	}
      }
    }
    return true;
  }

  function save($table, $idKey) {
    //if($this->changed){
	 //UPDATE or INSERT?
	 $res = $this->db->getOne("SELECT count(*) AS tot FROM " . $table . " WHERE " . $idKey . " = '" . $this->id . "'");
	 if(is_numeric($res) && $res > 0) { //UPDATE!
		//create a small portion of SQL for Update Statement based on the keys
		while(list($key,$val)=each($this->data)){
		  if($key!=$idKey){
			 if($val === NULL){
				$my_sql[] = $key . " = NULL";
			 }else{
				$my_sql[] = $key . " = '" . $val . "'";
			 }
		  }
		}
		$my_sql = implode(", ", $my_sql);
	
		//execute the query
		$res = $this->db->query("UPDATE " . $table . " SET " . $my_sql . " WHERE " . $idKey . " = '" . $this->id . "'");
		
		//if the query is dead, stop executio, output error
		if(DB::isError($res)){
		  raiseError($res);
		}
	 }else{	//INSERT!!!
		while(list($key,$val)=each($this->data)){
		  $keys[] = $key;
		  if($val === NULL){
			 $values[] = "NULL";
		  }else{
			 $values[] = "'" . $val . "'";
		  }
		}
		if(!in_array($idKey, $keys)) {
		  $keys[] = $idKey;
		  $values[] = "'" . $this->id . "'";
		}
		$keys = implode(",",$keys);
		$values = implode(",",$values);
		
		//execute query
		$res = $this->db->query("INSERT INTO " . $table . "(" . $keys . ") VALUES(" . $values . ")");
	
		//if the query is dead, stop executio, output error
		if(DB::isError($res)){
		  raiseError($res);
		}
	 }
    return true;
  }

  /**
   * purpose: delete data
   *
   * @return (bool)
   */
  function delete($table,$id){
    $res = $this->db->query("DELETE FROM " . $table . " WHERE " . $id . " = '" . $this->id . "'");
    if(DB::isError($res)){
      trigger_error($res->getMessage());
    }
    return true;
  }
  
  /**
   *****>> PRIVATE METHOD <<*****
   *
   * purpose: populate data from database based on the ID set
   * 					in the constructor
   *
   * @return (bool)
   */
  function load($table,$id){
    $res = $this->db->getRow("SELECT * FROM " . $table . " WHERE " . $id . " = '" . $this->id . "'",DB_FETCHMODE_ASSOC);
    if(DB::isError($res)){
      trigger_error($res->getMessage());
    }
    if (!$res)
      return false;
    $this->set_all($res);
    return true;
  }

  /**
   * sotf::getID()
   *
   * purpose: get the ID of this object
   *
   * @return (int)
   */
  function getID(){
    return $this->id;
  }
  
  /**
   * sotf :: set()
   * 
   * purpose: to set a property. If you want this property to
   * 					be the object ID, pass TRUE as a third parameter
   * 
   * @return (void)
   */
  function set($prop_name,$prop_value,$id=false){
    $this->changed = true;
    $this->data[$prop_name] = $prop_value;
    if($id){
      $this->id = $prop_value;
    }
  }
  
  /**
   * sotf :: setBlob()
   * 
   * purpose: to set a binary property.
   * 
   * @return (void)
   */
  function setBlob($prop_name,$prop_value){
    $this->changed = true;
    $this->data[$prop_name] = db_Wrap::escape_bytea($prop_value);
  }
  
  /**
   * sotf::get()
   * 
   * purpose: to get a property, will return FALSE
   * 					in case the property has not been set
   * 
   * @param $prop_name
   * @return 
   */
  function get($prop_name){
    if(!isset($this->data[$prop_name])){
      return false;
    }
    return $this->data[$prop_name];
  }
  
  /**
   * sotf::getBlob()
   * 
   * purpose: to get a binary property
   * 
   * @param $prop_name
   * @return 
   */
  function getBlob($prop_name){
    if(!isset($this->data[$prop_name])){
      return false;
    }
    return db_Wrap::unescape_bytea($this->data[$prop_name]);
  }
  
  /**
   * sotf::set_all()
   * 
   * purpose: set the whole data array
   * @return (bool)
   */
  function set_all($to_set){
    if(!is_array($to_set)){
      return false;
    }
    
    $this->data = $to_set;
    $this->changed = TRUE;
    return true;
  }
  
  /**
   * sotf::get_all()
   * 
   * purpose: get all elements of the data array
   * @return (array)
   */
  function get_all(){
    return $this->data;
  }
  
  /**
   * sotf::get_keys()
   * 
   * purpose: get all keys from the data array
   * @return (array)
   */
  function get_keys(){
    reset($this->data);
    while(list($key,$val)=each($this->data)){
      $my_keys[] = $key;
    }
    return $my_keys;
  }
  
  /**
   * sotf::debug()
   * 
   * purpose: turn debugging on
   * @return (void)
   */
  function debug(){
    echo "<br>====================== SOTF DEBUG ===========================<br>";
    echo "<b>Object ID:</b> " . $this->id . "<br>";
    echo "<b>Object Data:</b> <pre>"; print_r($this->data); echo "</pre>";
    echo "<b>Object Changed:</b> "; if($this->changed){ echo "TRUE"; }else{ echo "FALSE";} echo "<br>";
    echo "<b>Object Database Handle:</b> <pre>"; print_r($this->db->dsn) . "</pre>";
  }					
}

?>