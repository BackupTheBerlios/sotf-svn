<?php 
// -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

class sotf_Station extends sotf_NodeObjectWithPerm {		

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
		$this->sotf_NodeObjectWithPerm('sotf_stations', $id, $data);
		if($id) {
			$roles = $this->loadRoles();
		}
	}

	function loadRoles() {
		// load roles
    /*
		$r = $this->db->getCol("SELECT id FROM sotf_roles WHERE object_id='$id'");
		while (list (, $val) = each ($r)) {
			$this->roles[$val['node_id'] . '_' . $val['id']] = new sotf_Role('sotf_station_roles', $val['node_id'], $val['id']);
		}
    */
	}

  function isNameInUse($stationName) {
    global $db;
    $res = $db->getOne("SELECT count(*) FROM sotf_stations WHERE name='". sotf_Utils::clean($stationName) . "'");
    if(DB::isError($res))
      raiseError($res);
    return $res;
  }

	function create($stationName, $desc) {
		global $nodeId;
		$st = & new sotf_Station();
		$st->set('name', $stationName);
		$st->set('description', $desc);
		$dir = $st->getDir();
		if(!is_dir($dir)) {
			mkdir($dir, 0775);
			mkdir("$dir/station", 0775);
		}
    parent::create();
	}
	
	function delete(){
		if(!$this->isLocal())
			raiseError("Can delete only local stations");
		// delete files from the repository
		sotf_Utils::erase($this->getDir());
		// propagate deletion to other nodes
    $this->createDeletionRecord();
		// delete from sql db
		return parent::delete();
	}

	function getDir() {
		return $this->repository->rootdir . '/' . $this->name;
	}

	function getStationDir() {
		return $this->getDir() . '/station';
	}

	function isLocal() {
		return is_dir($this->getDir()); 
	}

	/** removes logo of the station */
	function deleteLogo() {
	$dir = $this->getDir();
	$handle = opendir($dir);
	$found = false;
	while (($file = readdir($handle)) !== false) {
		if (preg_match('/^logo\./',$file)) {
		$found = true;
		break;
		}
	}
	closedir($handle);
	if ($found) {
		$success = unlink($dir . '/' . $file);
		if(!$success)
			error("Could not delete logo of station");	
		return $success;
	} else {
		return false;
	}
	}

	/**
	* Sets logo of the station
	*
	* @param	object	$file	sotf_File object represents the logo
	* @return	boolean	True if the function succeeded, else false
	* @use	$db
	* @use	$iconWidth
	* @use	$iconHeight
	*/
	function setLogo($file)
	{
		global $db,$iconWidth,$iconHeight;

		if ($file->type != "none")
		{
			$info = GetAllMP3info($file->getPath());
			if (($info['png']['width'] == $iconWidth) && ($info['png']['height'] == $iconHeight))
				if ($fp = fopen($file->getPath(),'rb'))
				{
					$data = fread($fp,filesize($file->getPath()));
					fclose($fp);
					$this->setBlob("icon",$data);
					return true;
				}
		}
		return false;
	} // end func setLogo

	/**
	* Gets logo of the station
	*
	* @return	string	Binary data contains the logo
	* @use	$db
	*/
	function getLogo()
	{
		global $db;

		return $this->getBlob("icon");
	} // end func getLogo

	/**
	* Sets jingle of the station.
	*
	* @param	object	$audiofile	sotf_AudioFile object represents the jingle
	* @return	boolean	True if the function succeeded, else false
	* @todo	Look for the old existing jingle. If the old one doesn't follow the current naming procedure, it will remain in the directory, and makes troubles
	* @use	$audioFormats
	*/
	function setJingle($audiofile)
	{
		global $audioFormats;

		$index = sotf_AudioCheck::getRequestIndex($audiofile);
		if (false === $index)
			return false;
		$dir = $this->getStationDir();
		$targetFile = $dir . '/' . 'jingle_' . $audiofile->getFormatFilename();
		$retval = copy($audiofile->getPath(), $targetFile);

		if(!$retval)
			return false;
			//return new PEAR_Error("Could not move file $fromFile to its location");
		//chmod($targetFile, 0770);
		return true;
	}

	/**
	* Gets a jingle of the station.
	*
	* @param	integer	$index	Format index of the jingle in the $audioFormats global variable
	* @return	mixed	Returns the path of the jingle if exist, else return boolean false
	* @use	$audioFormats
	*/
	function getJingle($index)
	{
		global $audioFormats;

		$file = $this->getStationDir() . '/jingle_' . sotf_AudioCheck::getFormatFilename($index);
		if (is_file($file) && !is_file($file.'.lock'))
		{
			return $file;
		}
		else
		{
			return false;
			//return new PEAR_Error($stationId . " has no jingle!");
		}
	}

	/** get number of published programmes */
	function numProgrammes($onlyPublished = true) {
		if(isset($this->numProgrammes))
			return $this->numProgrammes;
    $sql = "SELECT COUNT(*) FROM sotf_programmes WHERE station_id = '" . $this->id . "' ";
    if($onlyPublished)
      $sql .= " AND published='t'";
    $count = $this->db->getOne($sql);
    if (DB::isError($count))
      return 0;
    else
      return $count;
	}

	/** list programmes */
	function listProgrammes($start, $num, $onlyPublished = true) {
		$id = $this->id;
		$sql = "SELECT * FROM sotf_programmes WHERE station_id = '$id' ";
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
			raiseError($res);
		foreach($res as $item) {
			$list[] = new sotf_Programme($item['id'], $item);
		}
		return $list;
	}

	/**
	 * @method listSeries
	 * @return array of sotf_Series objects
	*/
	function listSeries() {
		$id = $this->id;
		$slist = $this->db->getAll("SELECT * FROM sotf_series WHERE station_id='$id' ");
		if(DB::isError($slist))
			raiseError($slist);
		while (list (, $val) = each ($slist)) {
			$retval[] = new sotf_Series($val['id'], $val);
		}
		return $retval;
	}

	/**
	 * @method static listStations
	 * @return array of sotf_Series objects
	*/
	function listAll() {
		global $db;
		$res = $db->getAll("SELECT * FROM sotf_stations ORDER BY name");
		if(DB::isError($res))
      raiseError($res);
    foreach($res as $st) {
			$slist[] = new sotf_Station($st['id'], $st);
    }
		return $slist;
	}

	/**
	 * @method static listStationNames
	 * @return array of name
	*/
	function listStationNames($localOnly = false) {
		global $nodeId, $db;
		$sql = "SELECT name FROM sotf_stations";
		if($localOnly)
			$sql .= " WHERE node_id='$nodeId' ";
		$sql .= " ORDER BY name";
		return $db->getCol($sql);
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
