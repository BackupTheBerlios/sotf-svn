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
		//$res = $this->db->getOne("SELECT count(*) AS tot FROM " . $this->tablename . " WHERE " . $this->idKey . "='" . $this->id . "' ");
		//if(is_numeric($res) && $res > 0) { //UPDATE!
		if($this->exists()) {
			$this->update();
		}
	 } else {
		$this->create();
	 }
	}

	/** updates fields in 'data' except binary fields */
	function update() {
		reset($this->data);
		while(list($key,$val)=each($this->data)){
			if($key != $this->idKey && !in_array($key, $this->binaryFields)) {
				if($val === NULL){
					$my_sql[] = $key . " = NULL";
				}else{
					$my_sql[] = $key . " = '" . sotf_Utils::magicQuotes($val) . "'";
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

	/** creates db record with all fields from 'data' except binary fields */
	function create() {
		reset($this->data);
		while(list($key,$val)=each($this->data)){
			if(in_array($key, $this->binaryFields))
				continue;
			$keys[] = $key;
			if($val === NULL){
				$values[] = "NULL";
			}else{
				$values[] = "'" . sotf_Utils::magicQuotes($val) . "'";
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
		if (count($res)==0)
			raiseError("No such id: '$this->id' in '$this->tablename'");
		$this->setAll($res);
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
	 * purpose: to set a property. If you want this property to
	 *					be the object ID, pass TRUE as a third parameter
	 * 
	 * @return (void)
	 */
	function set($prop_name, $prop_value, $id=false){
		$this->changed = true;
		$this->data[$prop_name] = $prop_value;
		if($id){
			$this->id = $prop_value;
		}
	}

	/**
	 * sotf :: set()
	 * 
	 * purpose: to set a property. If you want this property to
	 *					be the object ID, pass TRUE as a third parameter
	 * 
	 * @return (void)
	 */
	function setWithParam($prop_name, $param_name='', $id=false) {
		if(!$param_name)
			$param_name = $prop_name;
		$this->set($prop_name, sotf_Utils::getParameter($param_name), $id);
	}
	
	/**
	 * sotf :: setBlob()
	 * 
	 * purpose: to set a binary property.
	 * 
	 * @return (void)
	 */
	function setBlob($prop_name, $prop_value){
		if(empty($prop_value))
			$v = 'NULL';
		else
			$v = "'" . sotf_Utils::magicQuotes($this->db->escape_bytea($prop_value)) . "'";
		$res = $this->db->query("UPDATE " . $this->tablename . " SET $prop_name = $v WHERE " . $this->idKey . "='" . $this->id . "' ");
		if(DB::isError($res))
			raiseError("Error in setBlob: $res");
		$this->data[$prop_name] = $v;
	}
	
	/**
	 * sotf::get()
	 * 
	 * purpose: to get a property, will return FALSE
	 *					in case the property has not been set
	 * 
	 * @param	string	$prop_name	Undocumented by Alex
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
	 * @param	string	$prop_name	Undocumented by Alex
	 * @return 
	 */
	function getBlob($prop_name){
		if(!isset($this->data[$prop_name])){
			return false;
		}
		return $this->db->unescape_bytea($this->data[$prop_name]);
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
		$this->changed = TRUE;
		return true;
	}
	
	/**
	 * 
	 * purpose: get all elements of the data array
	 * @return (array)
	 */
	function getAll(){
		return $this->data;
	}
	
	/**
	 * 
	 * purpose: get all keys from the data array
	 * @return (array)
	 */
	function getKeys(){
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