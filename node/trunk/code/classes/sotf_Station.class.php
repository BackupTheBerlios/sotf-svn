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
		global $config;

		$this->set('name', $stationName);
		$this->set('description', $desc);
		parent::create();
		$dir = $this->getDir();
		if(!is_dir($dir)) {
			mkdir($dir, 0775);
			mkdir("$dir/station", 0775);
		}
		#return $this->id;
	}
	
	function delete(){
		if(!$this->isLocal())
			raiseError("Can delete only local stations");
		// delete files from the repository
		debug("deleting: ", $this->getDir());
		sotf_Utils::erase($this->getDir());
		// delete from sql db
		return parent::delete();
	}

	function getDir() {
	global $repository;

		$name = $this->get("name");
		if(empty($name))
			raiseError("this station has no name!");
		return $repository->rootdir . '/' . $name;
	}

	function getJingleDir() {
		return $this->getDir() . '/station';
	}

	function isLocal() {
		return is_dir($this->getDir()); 
	}

 	/** removes logo of the station */
	function deleteIcon() {
		$iconFile = $this->getJingleDir() . '/icon.png';
		if(is_readable($iconFile)) {
			parent::deleteIcon();
			if(!unlink($iconFile))
				addError("Could not delete icon file!");
		}
	}

	/**
	* Sets logo of the station
	*
	* @param	object	$file	sotf_File object represents the logo
	* @return	boolean	True if the function succeeded, else false
	* @use	$db
	* @use	$config['iconWidth']
	* @use	$config['iconHeight']
	*/
	function setIcon($file) {

		if(parent::setIcon($file)) {
			$iconFile = $this->getJingleDir() . '/icon.png';
			sotf_Utils::save($iconFile, $this->getIcon());
			return true;
		} else
			return false;
	} // end func setIcon

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


	/**
	 * @method static listStations
	 * @return array of sotf_Station objects
	*/
	function listStations($start, $hitsPerPage) {
	global $db;

		if(empty($start)) 
			$start = 0;
		$res = $db->limitQuery("SELECT * FROM sotf_stations ORDER BY name", $start, $hitsPerPage);
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
	 * @method static countAll
	 * @return count of available objects
	*/
	function countAll() {
		global $db;

		return $db->getOne("SELECT count(*) FROM sotf_stations");
	}

}
