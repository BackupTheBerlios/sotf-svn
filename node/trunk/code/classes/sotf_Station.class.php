<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-

/* 
 * $Id$
 *
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *				at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

/**
* Models a radio station
*/
class sotf_Station extends sotf_ComplexNodeObject {		

	var $numProgrammes;
	
	var $roles;

	 /**
		 * Constructor: loads the object from database if ids are given
		 *
		 * @param string tablename name of SQL table to store
		 * @param string node node id
		 * @param string id id within node
	 */
	function sotf_Station($id='', $data=''){
		$this->sotf_ComplexNodeObject('sotf_stations', $id, $data);
	}

	/** static */
	function isNameInUse($stationName) {
		global $db;

		$res = $db->getOne("SELECT count(*) FROM sotf_stations WHERE name='". sotf_Utils::clean($stationName) . "'");
		if(DB::isError($res))
			raiseError($res);
		return $res;
	}

	function delete() {
	  global $db;
	  // has to move contacts to another station if they are used elsewhere!
	  sotf_Contact::moveContactsFromStation($this);
	  parent::delete();
	}

	/** static: finds a station by its name
	 */
	function getByName($stationName) {
		global $db;

		$stationName = sotf_Utils::magicQuotes($stationName);
		$id = $db->getOne("SELECT id FROM sotf_stations WHERE name = '$stationName'");
		if(DB::isError($id))
			raiseError($id);
		if($id)
			return new sotf_Station($id);
		else
			return NULL;
	}

	function create($stationName, $desc) {
		$this->set('name', $stationName);
		$this->set('description', $desc);
		parent::create();
		//return $this->id;
	}

	/** private: Checks and creates subdirs if necessary. */
	function checkDirs() {
	  global $repository;
	  $dir = $this->getDir();
	  if(!is_dir($dir)) {
		 mkdir($dir, 0775);
		 mkdir("$dir/station", 0775);
	  }
	}

	/** the directory where contents are stored. */
	function getDir() {
	  global $repository;
	  $name = $this->get("name");
	  if(empty($name))
		 raiseError("this station has no name!");
	  return $repository->rootdir . '/' . $name;
	}

	/** returns the directory where metadata/jingles/icons are stored */
	function getMetaDir() {
	  return $this->getDir() . '/station';
	}

	/** get number of published programmes */
	function numProgrammes($onlyPublished = true) {
		global $db;

		if(isset($this->numProgrammes))
			return $this->numProgrammes;
		$sql = "SELECT COUNT(*) FROM sotf_programmes WHERE station_id = '" . $this->id . "' ";
		if($onlyPublished)
			$sql .= " AND published='t'";
		$count = $db->getOne($sql);
		if (DB::isError($count))
			return 0;
		else
			return $count;
	}

	/** list programmes */
	function listProgrammes($start, $hitsPerPage, $onlyPublished = true) {
	global $db;

		$id = $this->id;
		$sql = "SELECT * FROM sotf_programmes WHERE station_id = '$id' ";
		if($onlyPublished)
			$sql .= " AND published='t' ";
		$sql .= " ORDER BY entry_date DESC,track ASC";
		if(!$start) $start = 0;
		$res = $db->limitQuery($sql, $start, $hitsPerPage);
		if(DB::isError($res))
			raiseError($res);
		while (DB_OK === $res->fetchInto($item)) {
			$list[] = new sotf_Programme($item['id'], $item);
		}
		return $list;
	}

	/**
	 * @method listSeries
	 * @return array of sotf_Series objects
	*/
	function listSeriesData() {
	global $db;

		$id = $this->id;
		$slist = $db->getAll("SELECT * FROM sotf_series WHERE station_id='$id' ORDER BY name");
		if(DB::isError($slist))
			raiseError($slist);
		return $slist;
		/*
		while (list (, $val) = each ($slist)) {
			//$retval[] = new sotf_Series($val['id'], $val);
		}
		return $retval;
		*/
	}

	/**
	 * @method listSeries
	 * @return array of sotf_Series objects
	*/
	function listSeries() {
	global $db;

		$id = $this->id;
		$slist = $db->getAll("SELECT * FROM sotf_series WHERE station_id='$id' ORDER BY name ");
		if(DB::isError($slist))
			raiseError($slist);
		while (list (, $val) = each ($slist)) {
			$retval[] = new sotf_Series($val['id'], $val);
		}
		return $retval;
	}


	/** Static: count stations for given language	*/
	function countStations($language = 'none') {
	  global $db;
	  
	  $language = sotf_Utils::magicQuotes($language);
	  if($language != 'none')
		 return $db->getOne("SELECT count(*) FROM sotf_stations WHERE language LIKE '%$language%'");
	  else
		 return $db->getOne("SELECT count(*) FROM sotf_stations");
	}

	/**
	 * @method static listStations
	 * @return array of sotf_Station objects
	*/
	function listStations($start, $hitsPerPage, $mode='newest', $language = 'none') {
	  global $db;

		if(empty($start)) 
			$start = 0;
		if($mode=='newest')
		  $sortExpr = '  ORDER BY entry_date DESC ';
		else
		  $sortExpr = '  ORDER BY name ';
		$language = sotf_Utils::magicQuotes($language);
		if($language != 'none')
		  $whereExpr = " WHERE language LIKE '%$language%' ";
		else
		  $whereExpr = "";
		$res = $db->limitQuery("SELECT * FROM sotf_stations $whereExpr $sortExpr", $start, $hitsPerPage);
		if(DB::isError($res))
			raiseError($res);
		while (DB_OK === $res->fetchInto($st)) {
			$slist[] = new sotf_Station($st['id'], $st);
		}
		return $slist;
	}

	/**
	 * @method static listStationNames
	 * @return array of name
	*/
	function listStationNames() {
	global $db;

		$sql = "SELECT id, name FROM sotf_stations";
		//if($localOnly)
		//	$sql .= " WHERE node_id='$config['nodeId']' ";
		$sql .= " ORDER BY name";
		return $db->getAll($sql);
	}

	/**
	 * @method static lists languages that are used by stations
	 * @return array of lang ids
	*/
	function listStationLanguages() {
	  global $db, $page;
	  $llist = $db->getCol("SELECT DISTINCT(language) FROM sotf_stations");
	  foreach($llist as $l1) {
		 foreach(explode(',',$l1) as $l2) {
			if(!empty($l2)) {
			  $langs[$l2] = 1;
			}
		 }
	  }
	  reset($langs);
	  while(list($k,)=each($langs)) {
		 $langs[$k] = $page->getlocalized($k);
	  }
	  return $langs;
	}

	/**
	 * @method static countAll
	 * @return count of available objects
	*/
	function countAll() {
		global $db;

		return $db->getOne("SELECT count(*) FROM sotf_stations");
	}

}
