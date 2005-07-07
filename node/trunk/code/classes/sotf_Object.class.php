<?php // -*- tab-width: 2; indent-tabs-mode: 1; -*-

/*	
 * $Id$
 *
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri
 *				at MTA SZTAKI DSD, http://dsd.sztaki.hu
 *				Koulikov Alexey - alex@pvl.at
 */

/**
 * Basic class for SQL stored data
 *
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
  /** if it needs saving */
  var $changed;
  /** if the record exists in db */
  var $exists = NULL;

	var $error = NULL;

  /** list of fields which are treated as binary (image, sound) */
  var $binaryFields = array();


  /**
	* Constructor
	*
	* @param	integer $id
	* @return (void)
	*/
  function sotf_Object($tablename, $id='', $data='') {
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
		if(is_null($this->exists))
			return !empty($this->id) && (count($this->data) > 0);
		else
			return $this->exists;
  }

  function save() {
		//global $db;
		//if($this->id) {
		//$exists = $db->getOne("SELECT count(*) FROM " . $this->tablename . " WHERE " . $this->idKey . "='" . $this->id . "' ");
		if($this->exists()) {
		  $this->update();
		} else {
			$this->create();
		}
	}

  /** updates fields in 'data' except binary fields */
  function update() {
	 global $db, $repository;

	 reset($this->data);
	 while(list($key,$val)=each($this->data)){
		if($key != $this->idKey) {
		  if($val === NULL) {
			 $my_sql[] = $key . " = NULL";
		  } else {
			 //dump($val, 'val');
			 if(in_array($key, $this->binaryFields)) {
				//if(strpos($val, "'"))
				//	 raiseError("invalid character in binary field data");
				$my_sql[] = $key . " = '". addslashes($val) . "'";
			 } else {
				$my_sql[] = $key . " = '" . sotf_Utils::magicQuotes($val) . "'";
			 }
		  }
		}
	 }
	 $my_sql = implode(", ", $my_sql);

	 //execute the query
	 $res = $db->query("UPDATE " . $this->tablename . " SET " . $my_sql . " WHERE " . $this->idKey . "='" . $this->id . "' ");
	 
	 //if the query is dead, stop executio, output error
	 if(DB::isError($res)){
		raiseError($res);
	 }
	 $this->changed = false;
	 
	 // mark if this change requires a refresh in the metadata.xml file
	 $this->markParentToUpdate();
  }

  /** creates db record with all fields from 'data' */
  function create() {
	 global $db, $repository;

	 reset($this->data);
	 while(list($key,$val)=each($this->data)){
		$keys[] = $key;
		if($val === NULL) {
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
	 $res = $db->query("INSERT INTO " . $this->tablename . "(" . $keys . ") VALUES(" . $values . ")");
		
	 //if the query is dead, stop executio, output error
	 if(DB::isError($res)){
		 addError($res);
		 $this->error = $res->message . '(' . $res->code . ')';
		 return false;
	 }

	 $this->exists = true;
	 $this->changed = false;

	 // mark if this change requires a refresh in the metadata.xml file
	 $this->markParentToUpdate();

	 return true;
  }

  /**
	* purpose: delete data
	*
	* @return (bool)
	*/
  function delete(){
	 global $db;

	 $res = $db->query("DELETE FROM " . $this->tablename . " WHERE " . $this->idKey . " = '" . $this->id . "'");
	 if(DB::isError($res)){
		raiseError($res);
	 }
	 $this->exists = false;
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
		global $db;
	  
		$res = $db->getRow("SELECT * FROM " . $this->tablename . " WHERE " . $this->idKey . " = '" . $this->id . "'",DB_FETCHMODE_ASSOC);
		if(DB::isError($res)){
			raiseError($res);
		}
		if (count($res) > 0) {
			$this->data = $res;
			if($this->data[$this->idKey] != $this->id) {
				raiseError("returned id does not match with original id");
			}
			$this->exists = true;
		} else {
			logger('WARNING', "No such id: '$this->id' in '$this->tablename'");
			$this->data = array();
			$this->exists = false;
		}
  }

  function find() {
		global $db;
		
		reset($this->data);
		while(list($key,$val)=each($this->data)){
			//if($key != $this->idKey && !in_array($key, $this->binaryFields)) {
			if(!in_array($key, $this->binaryFields)) {
				$my_sql[] = $key . " = '" . sotf_Utils::magicQuotes($val) . "'";
			}
		}
		$my_sql = implode(" AND ", $my_sql);
		
		//execute the query
		$res = $db->getCol("SELECT $this->idKey FROM $this->tablename WHERE $my_sql ");
		if(count($res) > 1)
			raiseError("not unique");
		if(count($res) == 1 ) {
			//debug("find()", $res[0]);
			$this->id = $res[0];
			$this->load();
			$this->exists = true;
		} else {
			$this->exists = false;
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
	 global $db;

	 $this->changed = true;
	 if(in_array($prop_name, $this->binaryFields)) {
		debug("set blob", $prop_name);
		$prop_value = $db->escape_bytea($prop_value);
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
	 global $db;

	 if(!is_array($to_set)){
		raiseError("array is expected in setAll");
	 }
	 $this->data = $to_set;
	 if($this->data[$this->idKey]) {
		$this->id = $this->data[$this->idKey];
	 }
	 if(count($this->binaryFields) > 0 ) {
		// translate binary fields
		reset($this->binaryFields);
		while(list(,$bf) = each($this->binaryFields)) {
		  $this->data[$bf] = $db->escape_bytea($this->data[$bf]);
		}
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

	/** similar to setWithParam, but parameter value may have text conversions (strip tags) */
  function setWithTextParam($prop_name, $param_name='') {
	 if(!$param_name)
		$param_name = $prop_name;
	 $this->set($prop_name, strip_tags(sotf_Utils::getParameter($param_name)));
  }

	/** similar to setWithParam, but parameter should be an URL here */
  function setWithUrlParam($prop_name, $param_name='') {
		global $page;
		if(!$param_name)
			$param_name = $prop_name;
		$url =  sotf_Utils::getParameter($param_name);
		if($url != 'http://') {
			if(sotf_Utils::is_valid_URL($url)) {
				$this->set($prop_name, $url);
				return true;
			} else {
				$page->addStatusMsg("invalid_url");
				return false;
			}
		}
		return true;
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
	 global $db;

	 if(!isset($this->data[$prop_name])){
		return false;
	 } else {
		if(in_array($prop_name, $this->binaryFields)) {
		  debug("get blob", $prop_name);
		  return $db->unescape_bytea($this->data[$prop_name]);
		} else {
		  return $this->data[$prop_name];
		}
	 }
  }

  /** Returns value for a bool, translating SQL notation of true/false into PHP notation. */
  function getBool($prop_name) {
		if(isset($this->data[$prop_name]) && $this->data[$prop_name] == 't') {
			return true;
		} else {
			//TODO: nemmuxik debug("FALSE", $this->data[$prop_name]);
			return false;
		}
  }
	
  /**
	* 
	* purpose: get all elements of the data array
	* @return (array)
	*/
  function getAll(){
	 global $db;

	 $retval = $this->data;
	 if(count($this->binaryFields) > 0 ) {
		// translate binary fields
		reset($this->binaryFields);
		while(list(,$bf) = each($this->binaryFields)) {
		  $retval[$bf] = $db->unescape_bytea($retval[$bf]);
		}
	 }
	 return $retval;
  }

	function getAllForHTML() {
		global $db;
		$retval = $this->data;
		if(count($this->binaryFields) > 0 ) {
			// translate binary fields
			reset($this->binaryFields);
			while(list(,$bf) = each($this->binaryFields)) {
				$retval[$bf] = $db->unescape_bytea($retval[$bf]);
			}
		}
		$retval = sotf_Utils::toHTML($retval);
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

	/** decides if there's a need to update metadata description for parent object, and schedules it */
	function markParentToUpdate() {
		global $repository;
		$mainObjId = $this->getMainObjectId();
		debug("parentUpdate", $mainObjId);
		if($mainObjId) {
			$mainObj = $repository->getObject($mainObjId);
			if(is_a($mainObj, 'sotf_nodeobject') && $mainObj->isLocal()) {
				$this->addToUpdate('updateMeta', $mainObjId);
			}
		}		
	}

	function getMainObjectId() {
		//debug("class", get_class($this));
		switch($this->tablename) {
		case "sotf_user_permissions":
		case "sotf_object_roles":
			return $this->get("object_id");
		case 'sotf_rights':
		case 'sotf_extradata':
		case 'sotf_links':
		case 'sotf_prog_topics':
			// case 'sotf_other_files':
			// case 'sotf_media_files':
			// case 'sotf_prog_refs':
			// case 'sotf_prog_ratings':
			// case 'sotf_prog_stats':
			return $this->get('prog_id');
		default:
			return NULL;
		}
  }

	/** static */
	function addToUpdate($table, $id) {
		global $db;
		$exists = $db->getOne("SELECT count(*) FROM sotf_to_update WHERE tablename='$table' AND row_id='$id'");
		if(!$exists)
			$db->query("INSERT INTO sotf_to_update (tablename, row_id) VALUES('$table','$id')");
	}

	/** static */
	function doUpdates() {
		global $db, $repository;
		debug("object updates started");
		$list = $db->getAll("SELECT * FROM sotf_to_update");
		while(list(,$item) = each($list)) {
			$db->begin(true);
			$tablename = $item['tablename'];
			$rowId = $item['row_id'];
			debug("to_update", "$tablename, $rowId");
			switch($tablename) {
			case 'ratingUpdate':
				$rating = new sotf_Rating();
				$rating->updateInstant($rowId);
				break;
			case 'sotf_stats':
				$obj = new sotf_Statistics($rowId);
				if($obj->exists())
					$obj->updateStats();
				break;
			case 'updateMeta':
				$obj = $repository->getObject($rowId);
				if(is_object($obj))
					$obj->saveMetadataFile();
				break;
			default:
				logError("Unknown to_update type: " . $tablename);
			}
			$db->query("DELETE FROM sotf_to_update WHERE tablename='$tablename' AND row_id='$rowId'");
			$db->commit();
		}
		debug("object updates finished");
	}

  /**
	* sotf::debug()
	* 
	* purpose: turn debugging on
	* @return (void)
	*/
  function debug($name=''){
	 echo "<br><b>$name</b>====================== SOTF DEBUG ===========================<br>";
	 echo "<b>Object ID:</b> " . $this->id . "<br>";
	 echo "<b>Object Data:</b> <pre>"; print_r($this->data); echo "</pre>";
	 echo "<b>Object Changed:</b> "; if($this->changed){ echo "TRUE"; }else{ echo "FALSE";} echo "<br>";
	 echo "<b>Object Database Handle:</b> <pre>"; print_r($db->dsn) . "</pre>";
  }					
}

?>