<?php 
// -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

class sotf_Station extends sotf_RepBase {		

	var $numProgrammes;
	
	var $roles;

   /**
     * Constructor: loads the object from database if ids are given
     *
     * @param string tablename name of SQL table to store
     * @param string node node id
     * @param string id id within node
   */
  function sotf_Station($nodeId='', $id=''){
    parent::constructor('sotf_stations', $nodeId, $id);
    // load roles
    $r = $this->db->getAll("SELECT node_id, id FROM sotf_station_roles WHERE station_id='$id' AND node_id='$nodeId'");
    while (list (, $val) = each ($r)) {
      $this->roles[$val['node_id'] . '_' . $val['id']] = new sotf_Role('sotf_station_roles', $val['node_id'], $val['id']);
    }
    // load access rights
  }

  function create($station, $desc) {
  	global $nodeId;
	$st = & new sotf_Station();
	$st->id = $station;
	$st->set('id', $station);
	$st->set('description', $desc);
	$st->set('node_id', $nodeId);
	$dir = $st->getDir();
	if(!is_dir($dir)) {
	  mkdir($dir, 0775);
	  mkdir("$dir/station", 0775);
	}
	$st->save();
	return $st;
  }
  
  function delete(){
    if(! $this->isLocal()) {
      error("Can delete only local stations");
      return false;
    }
    // delete files from the repository
    sotf_Utils::erase($this->getDir());
    // propagate deletion to other nodes
	 $this->createDeletionRecord();
    // delete from sql db
    return parent::delete();
  }

  function getDir() {
    return $this->repository->rootdir . '/' . $this->id;
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
	$sql = "SELECT COUNT(*) FROM sotf_programmes WHERE station_id = '" . $this->id . "' AND node_id='" . $this->node . "' ";
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
	 $node = $this->node;
    $sql = "SELECT id, node_id FROM sotf_programmes WHERE station_id = '$id' AND node_id='$node'";
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

  /**
   * @method listSeries
   * @return array of sotf_Series objects
  */
  function listSeries() {
    $id = $this->id;
	 $node = $this->node;
    $slist = $this->db->getAll("SELECT node_id, id FROM sotf_series WHERE station_id='$id' AND node_id='$node'");
    while (list (, $val) = each ($slist)) {
      $retval[] = new sotf_Series($val['node_id'], $val['id']);
    }
    return $retval;
  }

  /**
   * @method static listStations
   * @return array of sotf_Series objects
  */
  function listStations() {
    global $db;
    $res = $db->getAll("SELECT node_id, id FROM sotf_stations ORDER BY name");
    if(DB::isError($res))
		raiseError($res);
	 foreach($res as $st) {
      $slist[] = new sotf_Station($st['node_id'], $st['id']);
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
   * @method static numStations
   * @return array of name
  */
  function numStations() {
    global $db;
    return $db->getOne("SELECT count(*) FROM sotf_stations");
  }

}
