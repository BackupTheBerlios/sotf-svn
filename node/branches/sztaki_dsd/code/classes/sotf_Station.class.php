<?php //-*- tab-width: 3; indent-tabs-mode: 1; -*-

/***
 * Station Class
 * purpose: to represent a SOTF STATION :)
 * Author: Alexey Koulikov - alex@pvl.at, alex@koulikov.cc
 ************/
class sotf_Station extends sotf_Base {		

	var $numProgrammes;

  /**
   * sotfStation::sotfStation()
   * 
   * el constructor
   * 
   * @param $id, $db_handle
   * @return (void)
   */
  function sotf_Station($id='', $data='') {
    $this->sotf_Base($id, $data);
  }

  /**
   * prupose: populate the object from the database
   * @return
   */
  function load(){
    //fetch data from database and fill values
    return parent::load("sotf_stations","station");
  }

  function create($station, $desc) {
  	global $nodeId;
	$st = & new sotf_Station();
	$st->id = $station;
	$st->set('station', $station);
	$st->set('description', $desc);
	$st->set('node', $nodeId);
    $dir = $st->getDir();
    if(!is_dir($dir)) {
      mkdir($dir, 0775);
      mkdir("$dir/station", 0775);
    }
    $st->save();
    return $st;
  }
  
  /**
   * purpose: to commit the changes made to the object with
   * 					the database.
   * @return (bool)
   */
  function save(){

    $this->data['last_change'] = db_Wrap::getTimestampTz();
    return parent::save("sotf_stations","station");
  }

  /**
   * sotfShow::delete()
   *
   * purpose: to delete data from the tables
   *
   * @return (bool)
   */
  function delete(){
    if(! $this->isLocal()) {
      error("Can delete only local stations");
      return false;
    }
    // delete files from the repository
    sotf_Utils::erase($this->getDir());
    // delete programmes of the station
    // TODO getallprogrammes: call delete
    // delete user permissions
    $this->db->query("DELETE FROM sotf_user_group WHERE station = '" . $this->id . "'");
    // propagate deletion to other nodes
    $data = array( 'what' => 'station',
		   'del_time' => db_Wrap::getTimestampTZ(),
		   'node' => $GLOBALS['nodeId']);
    sotf_Base::saveDataWithId("sotf_deletions", 'id', $this->id, $data);
    // delete station description
    return parent::delete("sotf_stations","station");
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

  /** sets logo of the station */
  /*function setLogo($fromFile) {
    $dir = $this->getDir();
      $parts = explode('.',$fromFile);
    if (count($parts) > 1) {
      $targetFile = $dir . '/logo.' . $parts[count($parts)-1];
    } else {
      $targetFile = $dir . '/logo';
    }
    $this->deleteLogo();
    $retval = rename($fromFile, $targetFile);
    if(!$retval)
      error("Could not move file $fromFile to its location");
    chmod($targetFile, 0770);
    return $targetFile;
  }*/
	
  /** returns the path of the logo of the station */
  /*function getLogo() {
    $dir = $this->getDir();
    if (is_dir($dir)) {
      $handle = opendir($dir);
    }
    if ($handle) {
      $found = false;
      while (($file = readdir($handle)) !== false) {
	if (preg_match('/^logo/',$file)) {
	  $found = true;
	  break;
	}
      }
      closedir($handle);
      if ($found) {
	return $dir . '/' . $file;
      } else {
	return new PEAR_Error($this->id . " has no logo!");
      }
    } else {
      return new PEAR_Error("Cannot open station subdirectory in station " . $this->id . "!");
    }
  }*/

  function getNode() {
    $id = $this->id;
    $node = $this->db->getRow("SELECT n.* FROM sotf_nodes n, sotf_stations s WHERE n.id=s.node AND s.station='$id'");
    if(!DB::isError($node))
      return $node;
    else
      return null;
  }

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

  /*function setJingle($fromFile) {
    $dir = $this->getDir();
    $targetFile = $dir . '/' . JINGLE;
    $retval = rename($fromFile, $targetFile);
    if(!$retval)
      return new PEAR_Error("Could not move file $fromFile to its location");
    //chmod($targetFile, 0770);
    return $targetFile;
  }*/

  /** sets jingle24 of the station */
  /*function setJingle24($fromFile) {
    $dir = $this->getDir();
    $targetFile = $dir . '/' . JINGLE24;
    $retval = rename($fromFile, $targetFile);
    if(!$retval)
      return new PEAR_Error("Could not move file $fromFile to its location");
    //chmod($targetFile, 0770);
    return $targetFile;
  }*/

  /** returns the path of the jingle of the station */
  /*function getJingle() {
    $file = $this->getDir();
    if (is_file($file) && !is_file($file.'.lock')) {
      return $file;
    } else {
      return new PEAR_Error($stationId . " has no jingle!");
    }
  }*/

  /** returns the path of the jingle24 of the station */
  /*function getJingle24() {
    $file = $this->getDir();
    if (is_file($file) && !is_file($file.'.lock')) {
      return $file;
    } else {
      return new PEAR_Error($stationId . " has no jingle24!");
    }
  }*/

  /** get number of published programmes */
  function numProgrammes($onlyPublished = true) {
  	if(isset($this->numProgrammes))
  		return $this->numProgrammes;
	$sql = "SELECT COUNT(*) FROM sotf_programmes WHERE station = '$id'";
    if($onlyPublished)
      $sql .= " AND published='t'";
    $id = $this->id;
    $count = $this->db->getOne($sql);
    if (DB::isError($count))
      return 0;
    else
      return $count;
  }

  /** list programmes */
  function listProgrammes($start, $num, $onlyPublished = true) {
	global $db;

    $id = $this->id;
    $sql = "SELECT * FROM sotf_programmes WHERE station = '$id' ";
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

  /** get the list of series at the station*/
  function listSeries() {
    return sotf_Series::listSeries($this->id);
  }

  /*
  function listStationsAndCounts() {
    global $db;
    $sql = "SELECT s.*, count(i.track) AS nump FROM sotf_stations AS s LEFT JOIN sotf_programmes AS i ON s.station=i.station AND i.published='t' GROUP BY s.station ORDER BY s.station";
    $res = $db->getAll($sql);
    // if(isError)...
	foreach($res as $st) {
		$s = new sotf_Station($st['station'], $st);
		$s->numProgrammes = $s->data['nump'];
		unset($s->data['nump']);
		$slist[] = $s;
	}
    return $slist;
  }
*/

  function listStations() {
    global $db;
    $sql = "SELECT * FROM sotf_stations ORDER BY station";
    $res = $db->getAll($sql);
    // if(isError)...
	foreach($res as $st) {
      $slist[] = new sotf_Station($st['station'], $st);
	}
    return $slist;
  }

  /** station.php search.php */
  function listStationNames($localOnly = false) {
    global $nodeId, $db;
    $sql = "SELECT station FROM sotf_stations";
    if($localOnly)
      $sql .= " WHERE node='$nodeId' ";
    $sql .= " ORDER BY station";
    return $db->getCol($sql);
  }

  function numStations() {
    global $db;
    return $db->getOne("SELECT count(*) FROM sotf_stations");
  }

  /*
  function getURL($station) {
    return $this->db->getOne("SELECT n.url FROM sotf_nodes n, sotf_stations s WHERE n.id=s.node AND s.station='$station'");
  }
  */

}
