<?php //-*- tab-width: 3; indent-tabs-mode: 1; -*-

/***
 * Show Class
 * purpose: to represent a SOTF SHOW :)
 * Author: Alexey Koulikov - alex@pvl.at, alex@koulikov.cc
 ************/

class sotf_Programme extends sotf_NodeObject {
  
  var $topics;
  var $listenTotal;
  var $downloadTotal;
  var $visitTotal;
  var $rating;
  var $ratingTotal;
  var $genre;
  var $station;
  var $series;

  var $roles;
  var $extradata;
  var $other_files;
  var $media_files;
  var $links;
  var $rights;
  var $refs;

  /**
   * constructor
   */
  function sotf_Programme($id='', $data='') {
	 parent::constructor('sotf_programmes', $id, $data);
  }
  
  /** finds the next available track id within the station ($track may be empty) */
  function createTrackId($station, $date, $track) {
    global $db;
    if(!$track)
      $track = '1';
    $res = $db->getOne("SELECT max(track) FROM sotf_programmes WHERE station='$station' AND entry_date='$date'");
    if(DB::isError($res)) {
      raiseError($res);
    }
    if(empty($res))
      return $track;
    else
      return $res+1;
  }
		
  /** static method to create a new programme */
  // todo: this is not good enough
  function create($station, $track='') {
    global $user;
    $prg = new sotf_Programme();
    $prg->set('station', $station);
    $id = new sotf_Id($station, NULL, $track);
    $id->track = sotf_Programme::getNextTrackId($station, $id->date, $track);
    $prg->set('entry_date', $id->date);
    $prg->set('owner', $user->name);
    while(1) {
      $prg->set('track', $id->track);
      $prg->id = $id->toString();
      if($prg->save()) { // this will also create the required directories via setMetadataFile !!
        debug("created new programme", $prg->id);
        return $prg;
      }
      $id->track = sotf_Programme::getNextTrackId($station, $id->date, $id->track);
    }
  }

  /*
  function loadOtherFiles() {
    if(empty($this->files)) {
      $this->files = & new sotf_FileList();
      $this->files->getDir($this->getOtherFilesDir());
    }
  }

  function loadAudioFiles() {
    if(empty($this->audioFiles)) {
      $this->audioFiles = & new sotf_FileList();
      $this->audioFiles->getAudioFromDir($this->getAudioDir());
    }    
  }
  */

  /** deletes the program, and all its data and files
   */
  function delete(){
	 $this->createDeletionRecord();
	 sotf_Utils::erase($this->getDir());
    return parent::delete();
  }

  /** returns the directory where programme files are stored */
  function getDir() {
    return $this->repository->rootdir . '/' . $this->data['station'] . '/' . $this->data['entry_date'] . '/' . $this->data['track'];
  }

  /** returns directory where audio files are stored for the programme */
  function getAudioDir() {
    return $this->getDir() . '/audio';
  }

  /** returns directory where other files are stored for the programme */
  function getOtherFilesDir() {
    return $this->getDir() . '/files';
  }

  function getStation() {
    return $this->data['station'];
  }

  function getSeries() {
    return $this->data['series'];
  }

  function isLocal() {
    return is_dir($this->getDir()); 
  }

  function isEditable() {
    global $user;
    return ($this->get('owner') == $user->name) || sotf_Permission::get('write', $this->getStation());
  }

  function exists() {
    return isset($data['id']);
  }

  /** makes a new item available, announces to other nodes */
  function publish() {
    $this->data['published'] = 't';
    $this->save();
  }

  /** marks as withdrawn, but not deletes it */
  function withDraw() {
    $this->data['published'] = 'f';
    $this->save();
  }

  function deleteStats() {
    $id = $this->id;
    $this->db->query("DELETE FROM sotf_stats WHERE id='$id'");
  }

  function addStat($type) {
    if($type != 'listens' && $type != 'downloads')
      die("addStat: type should be 'listens' or 'downloads'");
    $db = $this->db;
    $now = getdate();
    $year = $now['year'];
    $month = $now['mon'];
    $day = $now['mday'];
    $week = date('W');
    $station = $this->getStation();
    $track = $id->trackId;
    $id = $this->id;
    $where = " WHERE id='$id' AND year='$year' AND month='$month' AND day='$day' AND week='$week'";
    $listens = $db->getOne("SELECT count(*) FROM sotf_stats $where");
    if($listens)
      {
        $db->query("UPDATE sotf_stats SET $type=$type+1 $where");
      }
    else
      {
        $db->query("INSERT INTO sotf_stats (id, station, year, month, week, day, $type) VALUES('$id','$station', '$year', '$month', '$week', '$day', '1')");
      }
  }

  function getStats() {
    $db = $this->db;
    $idStr = $this->id;
    $result = $db->getRow("SELECT sum(listens) AS listentotal, sum(downloads) AS downloadtotal FROM sotf_stats WHERE id='$idStr'");
    if(DB::isError($result))
      return array('listentotal'=> 0, 'downloadtotal' => 0);
    else {
      //debug("result", $result);
      if($result['listentotal'] == NULL)
        $result['listentotal'] = 0; // cosmetics
      if($result['downloadtotal'] == NULL)
        $result['downloadtotal'] = 0; // cosmetics
      return $result;
    }
  }

  function getRefs() {
    $db = $this->db;
    $id = $this->id;
    $result = $db->getAll("SELECT * FROM sotf_refs WHERE id='$id'" );
    if(DB::isError($result))
      return array();
    else
      return $result;
  }

  function deleteRefs() {
    $id = $this->id;
    $this->db->query("DELETE FROM sotf_refs WHERE id='$id'");
  }

  /** get news for index page */
  function getNewProgrammes($fromDay, $maxItems) {
    global $nodeId, $db;
    $sql = "SELECT i.* FROM sotf_programmes i, sotf_stations s WHERE i.station_id = s.id AND i.published='t' AND i.entry_date >= '$fromDay' ORDER BY i.entry_date DESC";
    $res =  $db->limitQuery($sql, 0, $maxItems);
    if(DB::isError($res))
      raiseError($res);
    $results = null;
    while (DB_OK === $res->fetchInto($row)) {
      $results[] = new sotf_Programme($row['id'], $row);
    }
    return $results;
  }


  // fix this!
  function myProgrammes($owner) {
    global $db;
    $sql = "SELECT * FROM sotf_programmes WHERE owner = '$owner' ORDER BY title, id";
    $plist = $db->getAll($sql);
    foreach($plist as $item) {
      $retval[] = new sotf_Programme($item['id'], $item);
    }
    return $retval;
  }


  /**
  * List files available for an item
  *
  * @return	array	Array of filenames
  */
  function listOtherFiles() {
    $this->loadOtherFiles();
    return $this->files->getFileNames();
  }

  /**
  * List audio files available for an item
  *
  * @return	array	Array of filenames
  */
  function listAudioFiles() {
    $this->loadAudioFiles();
    return $this->audioFiles->getFileNames();
  }

  /** private
	  Checks and creates subdirs if necessary.
   */
  function checkDirs() {
    $station = $this->getStation();
    $dir = $this->repository->rootdir . '/' . $station;
    if(!is_dir($dir))
      raiseError("Station $station does not exist!");
    $dir = $dir . '/' . $this->get('entry_date');
    if(!is_dir($dir))
      mkdir($dir, 0770);
    $dir = $dir . '/' . $this->get('track');
    if(!is_dir($dir)) {
      mkdir($dir, 0770);
    }
    if(!is_dir($dir . '/audio')) {
      mkdir($dir . '/audio', 0770);
    }
    if(!is_dir($dir . '/files')) {
      mkdir($dir . '/files', 0770);
    }
  }

  function getFilePath($id, $name) {
	return $this->rootdir . '/'. $id->stationId . '/' . $id->date . '/' . $id->trackId . '/files/' . $name;
  }

  function getAudioFilePath($id, $name) {
	return $this->rootdir . '/'. $id->stationId . '/' . $id->date . '/' . $id->trackId . '/audio/' . $name;
  }

  function deleteFile($name) {
	$name = sotf_Utils::getFileFromPath($name);
    $targetFile = $this->getOtherFilesDir() . '/'. $name;
    if (unlink($targetFile))
      return 0;
    else
      raiseError("Could not remove file $targetFile");
  }

  function deleteAudioFile($name) {
    $targetFile = $this->getAudioDir() . '/'. $name;
    if (unlink($targetFile))
      return 0;
    else
      raiseError("Could not remove file $targetFile");
  }

  function moveFileToUserDir($filename, $copy=false) {
    global $user;
    $source = $this->getOtherFilesDir . '/' . $filename;
    $target = $user->getUserDir() . '/' . $filename;
    while (file_exists($target))
      {
        $target .= "_1";
      }
    if (is_file($source))
      {
        if($copy)
          copy($source,$target);
        else {
          rename($source,$target);
          //$this->files->remove($source);
        }
      }
  }

  function moveAudioToUserDir($filename, $copy=false) {
    global $user;
    $source = $this->getAudioDir . '/' . $filename;
    $target = $user->getUserDir() . '/' . $filename;
    while (file_exists($target))
      {
        $target .= "_1";
      }
    if (is_file($source))
      {
        if($copy)
          copy($source,$target);
        else {
          rename($source,$target);
          //$this->files->remove($source);
        }
      }
  }

  function setAudio($filename, $copy=false) {
    global $user;
	$filename = sotf_Utils::getFileFromPath($filename);
    $source = $user->getUserDir().'/'. $filename;
    if(!is_file($source))
      raiseError("no such file: $source");
    $srcFile = new sotf_AudioFile($source);
		$target = $this->getAudioDir() .  '/' . 'prg_' . $srcFile->getFormatFilename();
    if($srcFile->type == audio) {
      if($copy)
         $success = copy($source,$target);
      else
        $success = rename($source,$target);
      if(!$success)
        raiseError("could not copy/move $source");
      return true;
    }
    return false;
    //$this->audioFiles->add($target);
  }

  function getAudio($filename, $copy=false) {
    global $user;
	$filename = sotf_Utils::getFileFromPath($filename);
    //$source = $user->getUserDir().'/'. $filename;
    $source = $this->getAudioDir() . '/' . $filename;
    if(!is_file($source))
      raiseError("no such file: $source");
    $target = $user->getUserDir().'/'. $filename;
    while (file_exists($target)) {
      $target .= "_1";
    }
    if($copy)
       $success = copy($source,$target);
    else
      $success = rename($source,$target);
    if(!$success)
      raiseError("could not copy/move $source");
    return true;
  }

  function setOtherFile($filename, $copy=false) {
    global $user;
	$filename = sotf_Utils::getFileFromPath($filename);
    $source = $user->getUserDir().'/'. $filename;
    $target = $this->getOtherFilesDir() . '/' . $filename;
    while (file_exists($target)) {
      $target .= "_1";
    }
    if (is_file($source))
      {
        if($copy)
          copy($source,$target);
        else
          rename($source,$target);
      }
  }

  function getOtherFile($filename, $copy=false) {
    global $user;
	$filename = sotf_Utils::getFileFromPath($filename);
    $source = $this->getOtherFilesDir() . '/' . $filename;
    if(!is_file($source))
      raiseError("no such file: $source");
    $target = $user->getUserDir() . '/' . $filename;
    while (file_exists($target)) {
      $target .= "_1";
    }
    if($copy)
       $success = copy($source,$target);
    else
      $success = rename($source,$target);
    if(!$success)
      raiseError("could not copy/move $source");
    return true;
  }

  function saveMetadataFile() {
    $this->checkDirs();
    $xml = "<xml>\n";
    foreach($this->data as $key => $value) {
      $xml = $xml . "  <$key>" . htmlspecialchars($value) . "</$key>\n";
    }
    $xml = $xml . "</xml>\n";
    $file = $this->getDir() . '/metadata.xml';
    $fp = fopen("$file", "w");
    fwrite($fp, $xml);
    fclose($fp);
    // TODO: save more data from other tables as well
    return true;
  }

  /*
  // returns all *published* metadata after the given timestamp excluding items from the given node 
  function getSyncData($timestamp, $excludeNode) {
    if($excludeNode) {
      $sql = "SELECT i.*, s.node FROM sotf_items i, sotf_stations s WHERE i.station=s.station AND s.node != '$excludeNode' AND i.published='t'";
      if($timestamp)
	$sql .= " AND i.last_change >= '$timestamp' ";
    } else {
      $sql = "SELECT * FROM sotf_items";
      if($timestamp)
	$sql .= " WHERE last_change >= '$timestamp'";
    }
    return $db->getAll($sql, DB_FETCHMODE_ASSOC);
  }
*/

  /**
   * @method static countAll
   * @return count of available objects
  */
  function countAll() {
    global $db;
    return $db->getOne("SELECT count(*) FROM sotf_programmes WHERE published='t'");
  }


}

?>
