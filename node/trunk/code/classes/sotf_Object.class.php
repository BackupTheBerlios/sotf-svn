<?php 
// -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

/**
* Basic class for SQL stored data
*
* @author Andras Micsik - micsik@sztaki.hu, Tamas Kezdi - kezdi@sztaki.hu, Koulikov Alexey - alex@pvl.at, alex@koulikov.cc
*/
class sotf_Object {

	/** the table where this data is saved */
	var $tablename;
	/** the name of the field where the id is stored */
	var $idKey;
	/** the unique ID of the object */
	var $id = NULL;
	/** all the properties of the object */
	var $data = array();
	/** the repository to which this object belongs */
	var $repository;
	/** the db handle */
	var $db;
	/** if it needs saving */
	var $changed;

	/** list of fields which are treated as binary (image, sound) */
	var $binaryFields = array();


	/**
	 * Constructor
	*
	 * @param	integer $id
	 * @return (void)
	 */
	function sotf_Object($tablename, $id='', $data='') {
		// be careful because ''==0 in PHP :-(, so use NULL instead..
		global $repository;
		//debug("constructor", 'sotf_Object');
		$this->repository=$repository;
		$this->db = $repository->db;
		$this->tablename = $tablename;
		$this->idKey = 'id';
		if($id)
			$this->id = $id;
		$this->changed = false;
		if(is_array($data)) {
			$this->data = $data;
		} else {
			if($id)
				$this->load();
		}
	}						

	/** tells if this record is from database or not */
	function exists() {
		return !empty($this->id);
	}

	function save() {
    if($this->id) {
      $exists = $this->db->getOne("SELECT count(*) FROM " . $this->tablename . " WHERE " . $this->idKey . "='" . $this->id . "' ");
      if($exists) {
        $this->update();
        return;
      }
    }
		$this->create();
	}

	/** updates fields in 'data' except binary fields */
	function update() {
		reset($this->data);
		while(list($key,$val)=each($this->data)){
			if($key != $this->idKey) {
				if($val === NULL || $val == ''){
					$my_sql[] = $key . " = NULL";
				}else{
          //dump($val, 'val');
          if(in_array($key, $this->binaryFields)) {
            //if(strpos($val, "'"))
            //  raiseError("invalid character in binary field data");
            $my_sql[] = $key . " = '". addslashes($val) . "'";
          } else {
            $my_sql[] = $key . " = '" . sotf_Utils::magicQuotes($val) . "'";
          }
				}
			}
		}
		$my_sql = implode(", ", $my_sql);

    //execute the query
    $res = $this->db->query("UPDATE " . $this->tablename . " SET " . $my_sql . " WHERE " . $this->idKey . "='" . $this->id . "' ");
    
    //if the query is dead, stop executio, output error
    if(DB::isError($res)){
      raiseError($res);
    }
	}

	/** creates db record with all fields from 'data' */
	function create() {
		reset($this->data);
		while(list($key,$val)=each($this->data)){
			$keys[] = $key;
			if($val === NULL || $val == '') {
				$values[] = "NULL";
			} else {
        if(in_array($key, $this->binaryFields)) {
          $values[] = "'" . addslashes($val) . "'";
        } else {
          $values[] = "'" . sotf_Utils::magicQuotes($val) . "'";
        }
			}
		}
		if($this->id) {		//	because ''==0 in PHP :-(
			if(!$keys || !in_array($this->idKey, $keys)) {
				$keys[] = $this->idKey;
				$values[] = "'" . sotf_Utils::magicQuotes($this->id) . "'";
			}
		}
		$keys = implode(",",$keys);
		$values = implode(",",$values);
		
		//execute query
		$res = $this->db->query("INSERT INTO " . $this->tablename . "(" . $keys . ") VALUES(" . $values . ")");
		
		//if the query is dead, stop executio, output error
		if(DB::isError($res)){
			addError($res);
			return false;
		}
		return true;
	}

	/**
	 * purpose: delete data
	 *
	 * @return (bool)
	 */
	function delete(){
		$res = $this->db->query("DELETE FROM " . $this->tablename . " WHERE " . $this->idKey . " = '" . $this->id . "'");
		if(DB::isError($res)){
			raiseError($res);
		}
		return true;
	}
	
	/**
	 *****>> PRIVATE METHOD <<*****
	 *
	 * purpose: populate data from database based on the ID set
	 *					in the constructor
	 *
	 * @return (bool)
	 */
	function load(){
		$res = $this->db->getRow("SELECT * FROM " . $this->tablename . " WHERE " . $this->idKey . " = '" . $this->id . "'",DB_FETCHMODE_ASSOC);
		if(DB::isError($res)){
			raiseError($res);
		}
		if (count($res) > 0) {
      $this->data = $res;
      if($this->data[$this->idKey] != $this->id) {
        raiseError("returned id does not match with original id");
      }
    } else {
			logError("No such id: '$this->id' in '$this->tablename'");
      $this->data = array();
    }
	}

  function find() {
    reset($this->data);
		while(list($key,$val)=each($this->data)){
			if($key != $this->idKey && !in_array($key, $this->binaryFields)) {
        $my_sql[] = $key . " = '" . sotf_Utils::magicQuotes($val) . "'";
			}
		}
		$my_sql = implode(" AND ", $my_sql);
    
    //execute the query
    $res = $this->db->getCol("SELECT $this->idKey FROM $this->tablename WHERE $my_sql ");
    if(count($res) > 1)
      raiseError("not unique");
    if(count($res) == 1 ) {
      $this->id = $res[0];
      $this->load();
    }
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

	/** set the id */
	function setID($id){
	 $this->data[$this->idKey] = $id;
	 $this->id = $id;
	}
	
	/**
	 * sotf :: set()
	 * 
	 * purpose: to set a property.
	 * 
	 * @return (void)
	 */
	function set($prop_name, $prop_value){
		$this->changed = true;
    if(in_array($prop_name, $this->binaryFields)) {
      debug("set blob", $prop_name);
      $prop_value = $this->db->escape_bytea($prop_value);
    }
		$this->data[$prop_name] = $prop_value;
		if($prop_name == $this->idKey) {
			$this->id = $prop_value;
		}
	}

	/**
	 * 
	 * purpose: set the whole data array
	 * @return (bool)
	 */
	function setAll($to_set){
		if(!is_array($to_set)){
			raiseError("array is expected in setAll");
		}
		$this->data = $to_set;
		if($this->data[$this->idKey]) {
			$this->id = $this->data[$this->idKey];
		}
    foreach($this->binaryFields as $bf) {
      $this->data[$bf] = $this->db->escape_bytea($this->data[$bf]);
    }
		$this->changed = TRUE;
		return true;
	}

	/** Sets field 'prop_name' with the value of the CGI parameter 'param_name'. 
   * If 'param_name' is empty, 'prop_name' is used as parameter name.
	 */
	function setWithParam($prop_name, $param_name='') {
		if(!$param_name)
			$param_name = $prop_name;
		$this->set($prop_name, sotf_Utils::getParameter($param_name));
	}
	
	/**
	 * sotf::get()
	 * 
	 * purpose: to get a property, will return FALSE
	 *					in case the property has not been set
	 * 
	 * @return 
	 */
	function get($prop_name){
		if(!isset($this->data[$prop_name])){
			return false;
		} else {
      if(in_array($prop_name, $this->binaryFields)) {
        debug("get blob", $prop_name);
        return $this->db->unescape_bytea($this->data[$prop_name]);
      } else {
        return $this->data[$prop_name];
      }
    }
	}
	
	/**
	 * 
	 * purpose: get all elements of the data array
	 * @return (array)
	 */
	function getAll(){
		$retval = $this->data;
    foreach($this->binaryFields as $bf) {
      $retval[$bf] = $this->db->unescape_bytea($retval[$bf]);
    }
    return $retval;
	}
	
	/**
	 * 
	 * purpose: get all keys from the data array
	 * @return (array)
	 */
	function getKeys(){
    return array_keys($this->data);
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