<?php 
// -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

define("GUID_DELIMITER", ':');
define("TRACKNAME_LENGTH", 32);

class sotf_Programme extends sotf_ComplexNodeObject {
  
  var $topics;
  var $listenTotal;
  var $downloadTotal;
  var $visitTotal;
  var $rating;
  var $ratingTotal;
  var $genre;
  var $station;
  var $stationName;
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
    $this->binaryFields = array('icon', 'jingle');
	 $this->sotf_ComplexNodeObject('sotf_programmes', $id, $data);
	 if($id) {
		$this->stationName = $this->db->getOne("SELECT name FROM sotf_stations WHERE id='" . $this->get('station_id') . "'");
	 }
  }
  
  function generateGUID() {
	 $this->set('guid', $this->stationName . GUID_DELIMITER . $this->get('entry_date') . GUID_DELIMITER . $this->get('track'));
  }

  /** finds the next available track id within the station ($track may be empty) */
  function getNextAvailableTrackId() {
		$track = $this->get('track'); 
		if(!$track)
		  $track = '1';
		else
		  $track = substr($track, 0, TRACKNAME_LENGTH);
		$this->set('track', $track);
		$this->generateGUID();
		$count = 0;
		while($count < 50) {
		  $guid = $this->get('guid');
		  $res = $this->db->getOne("SELECT count(*) FROM sotf_programmes WHERE guid='$guid'");
		  if(DB::isError($res))
			 raiseError($res);
		  if($res==0)
			 return;
		  $track = $this->get('track'); 
		  $track++;
		  $this->set('track', $track);
		  $this->generateGUID();
		  $count++;
		}
  }

  function create($stationId, $track='') {
	 $this->set('station_id', $stationId);
	 $stationName = $this->db->getOne("SELECT name FROM sotf_stations WHERE id='" . $this->get('station_id') . "'");
	 if(DB::isError($stationName))
		raiseError($stationName);
	 if(empty($stationName))
		raiseError("station with id '$stationId' does not exist");
   if(empty($track))
     $track = 'prg';
	 $this->stationName = $stationName;
   $this->set('entry_date', date('Y-m-d'));
   $this->set('track', sotf_Utils::makeValidName($track, 32));
	 $this->getNextAvailableTrackId();
	 $count = 0;
    while($count < 20) {
      if(parent::create()) { // this will also create the required directories via setMetadataFile !!
        debug("created new programme", $this->get['guid']);
		  $this->checkDirs();
		  $this->saveMetadataFile();
        return true;
      }
		$this->getNextAvailableTrackId();
		$count++;
    }
	 raiseError("Could not create new programme");
  }

  function update() {
	 parent::update();
	 $this->saveMetadataFile();
  }

  function getStation() {
    return new sotf_Station($this->get('station_id'));
  }

  function getSeries() {
    $sid = $this->get('series_id');
    debug('soid',$sid);
    if(!empty($sid))
      return new sotf_Series($sid);
    else 
      return NULL;
  }

	function isLocal() {
		return is_dir($this->getDir()); 
	}

  function getAssociatedObjects($tableName, $orderBy) {
    $objects = $this->db->getAll("SELECT * FROM $tableName WHERE prog_id='$this->id' ORDER BY $orderBy");
    return $objects;
  }

  /** deletes the program, and all its data and files
   */
  function delete(){
	 $this->createDeletionRecord();
	 sotf_Utils::erase($this->getDir());
   return parent::delete();
  }

  /** returns the directory where programme files are stored */
  function getDir() {
    return $this->repository->rootdir . '/' . $this->stationName . '/' . $this->data['entry_date'] . '/' . $this->data['track'];
  }

  /** returns directory where audio files are stored for the programme */
  function getAudioDir() {
    return $this->getDir() . '/audio';
  }

  /** returns directory where other files are stored for the programme */
  function getOtherFilesDir() {
    return $this->getDir() . '/files';
  }

  function getFilePath($file) {
    if(!$this->isLocal())
      raiseError('no_such_file');
    if($file->tablename == 'sotf_media_files' && $file->get('main_content')=='t') {
      return $this->getAudioDir() . '/' . $file->get('filename');
    } else {
      return $this->getOtherFilesDir() . '/' . $file->get('filename');
    }
  }

  function isLocal() {
    return is_dir($this->getDir()); 
  }

  function exists() {
    return isset($data['id']);
  }

  /** makes a new item available, announces to other nodes */
  function publish() {
    $this->set('published','true');
    $this->update();
  }

  /** marks as withdrawn, but not deletes it */
  function withdraw() {
    $this->data['published'] = 'f';
    $this->update();
  }

  /** sets icon for programme */
	function setIcon($file)
	{
    if(parent::setIcon($file)) {
      $iconFile = $this->getDir() . '/icon.png';
      sotf_Utils::save($iconFile, $this->getBlob('icon'));
      return true;
		} else
      return false;
	} // end func setIcon


  function deleteStats() {
    $id = $this->id;
    $this->db->query("DELETE FROM sotf_stats WHERE id='$id'");
  }

  function addStat($filename, $type) {
    // TODO: if remote program, send this by XML-RPC!!
    if($type != 'listens' && $type != 'downloads' && $type != 'visits')
      raiseError("addStat: type should be 'listens' or 'downloads' or 'visits'");
    $db = $this->db;
    $now = getdate();
    $year = $now['year'];
    $month = $now['mon'];
    $day = $now['mday'];
    $week = date('W');
    $track = $id->trackId;
    $where = " WHERE prog_id='$this->id' AND year='$year' AND month='$month' AND day='$day' AND week='$week'";
    $id = $db->getOne("SELECT id FROM sotf_stats $where");
    if($id) {
      $obj = new sotf_NodeObject("sotf_stats", $id);
      $obj->set($type, $obj->get($type)+1);
      $obj->update();
    } else {
      $obj = new sotf_NodeObject("sotf_stats");
      $obj->setAll(array('prog_id' => $this->id,
                         'station_id' => $this->get('station_id'),
                         'year' => $year,
                         'month' => $month,
                         'week' => $week,
                         'day' => $day,
                         $type => 1));
      $obj->create();
    }
  }

  function getStats() {
    $db = $this->db;
    $idStr = $this->id;
    $result = $db->getRow("SELECT sum(visits) AS visits, sum(listens) AS listens, sum(downloads) AS downloads FROM sotf_stats WHERE prog_id='$idStr'");
    if(DB::isError($result))
      return array('visits'=>0, 'listens'=> 0, 'downloads' => 0);
    else {
      debug("STATS", $result);
      return $result;
    }
  }

  /** static */
  function getAllStats() {
    global $db;
    return $db->getRow("SELECT sum(visits) AS visits, sum(listens) AS listens, sum(downloads) AS downloads FROM sotf_stats");
  }

  /** static */
  function getFileStats() {
    global $db;
    return $db->getRow("SELECT sum(filesize) AS filesize, sum(play_length) AS play_length FROM sotf_media_files");
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
      $results[] = $row;
      if(!empty($row['icon'])) {
        sotf_Programme::cacheIcon($row['id'], $db->unescape_bytea($row['icon']));
      }
    }
    return $results;
  }

  /** private
	  Checks and creates subdirs if necessary.
   */
  function checkDirs() {
    $station = $this->stationName;
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

  function deleteFile($fid) {
    $table = $this->repository->getTable($fid);
    $file = new sotf_NodeObject($table, $fid);
    if($table == 'sotf_media_files' && $file->get('main_content') == 't')
      $filepath = $this->getAudioDir() . '/' . $file->get('filename');
    else
      $filepath = $this->getOtherFilesDir() . '/' . $file->get('filename');
    $file->delete();
    if (unlink($filepath))
      return 0;
    else
      raiseError("Could not remove file $filepath");
  }

  function setAudio($filename, $copy=false) {
    global $page;
    $source = $filename;
    if(!is_file($source))
      raiseError("no such file: $source");
    $srcFile = new sotf_AudioFile($source);
    $target = $this->getAudioDir() .  '/' . $this->get('track') . '_' . $srcFile->getFormatFilename();
    if($srcFile->type != 'audio')
      raiseError("this is not an audio file");
    if(is_file($target)) {
      raiseError($page->getlocalized('format_already_present'));
    }
    if($copy)
      $success = copy($source,$target);
    else
      $success = rename($source,$target);
    if(!$success)
      raiseError("could not copy/move $source");
    // save into database
    $this->saveFileInfo($target, true);
  }

  /** Returns an array containing info about the available audio files. */
  function listAudioFiles($mainContent = 'true') {
    $objects = $this->db->getAll("SELECT * FROM sotf_media_files WHERE prog_id='$this->id' AND main_content='$mainContent' ORDER BY filename");
    return $objects;
  }

  function selectFileToListen() {
      // TODO: write this better
    $files = $this->listAudioFiles();
    // if lowest bitrate is free, select that
    while(list(,$f) = each($files)) {
      if(preg_match("/^24kbps/", $f['format']) && $f['stream_access']=='t')
        return $f['id'];
    }
    reset($files);
    // return first free to strem
    while(list(,$f) = each($files)) {
      if($f['stream_access']=='t')
        return $f['id'];
    }
    return '';
  }

  /** Returns an array containing info about the available other files. */
  function listOtherFiles() {
    $objects = $this->db->getAll("SELECT * FROM sotf_other_files WHERE prog_id='$this->id' ORDER BY filename");
    return $objects;
  }

  function setOtherFile($filename, $copy=false) {
    global $user;
    $source =  sotf_Utils::getFileInDir($user->getUserDir(), $filename);
    $target = $this->getOtherFilesDir() . '/' . $filename;
    while (file_exists($target)) {
      $target .= "_1";
    }
    if (is_file($source))
      {
        if($copy)
          $success = copy($source,$target);
        else
          $success = rename($source,$target);
      }
    if(!$success)
      raiseError("could not copy/move $source to $target");
    // save into database
    $this->saveFileInfo($target, false);
  }

  function saveFileInfo($filepath, $mainContent = false) {
    // convert boolean into pgsql format
    if($mainContent)
      $mainContent = 'true';
    else 
      $mainContent = 'false';
    // save file info into database
    $file = new sotf_AudioFile($filepath);
    if($file->isAudio()) {
      $fileInfo = new sotf_NodeObject('sotf_media_files');
      $fileInfo->set('play_length', round($file->duration));
      $fileInfo->set('type', $file->type);
      $fileInfo->set('format', $file->getFormatFilename());
      $fileInfo->set('main_content', $mainContent);
    } else {
      $fileInfo = new sotf_NodeObject('sotf_other_files');
    }
    $fileInfo->set('prog_id', $this->id);
    $fileInfo->set('filename', $file->name);
    $fstat = stat($filepath);
    $fileInfo->set('filesize', $fstat['size']);
    $fileInfo->set('last_modified', $this->db->getTimestampTz($fstat['mtime']));
    $fileInfo->set('mime_type', $file->mimetype);
    $success = $fileInfo->create();
    if(!$success)
      raiseError("could not write into database");
  }

  function saveMetadataFile() {
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

  function sortTopicsByName($a, $b) {
    return strcmp($a['name'], $b['name']);
  }

  function getTopics() { 
    $topics = $this->getAssociatedObjects('sotf_prog_topics', 'id');
    for($i=0; $i<count($topics); $i++) {
      $topics[$i]['name'] = $this->repository->getTopicName($topics[$i]['topic_id']);
    }
    usort($topics, array('sotf_Programme', 'sortTopicsByName'));
    return $topics;
  }

  /**
   * @method static countAll
   * @return count of available objects
  */
  function countAll() {
    global $db;
    return $db->getOne("SELECT count(*) FROM sotf_programmes WHERE published='t'");
  }

  /** static returns programmes owned/edited by current user */
  function myProgrammes() {
    global $permissions, $db, $user;
		if(!isset($permissions->currentPermissions))
      return NULL;  // not logged in yet
    $sql = "SELECT  s.name AS station, se.title AS series, stats.visits, stats.listens, stats.downloads, rating.*, p.* FROM sotf_programmes p LEFT JOIN sotf_stations s ON p.station_id = s.id LEFT JOIN sotf_series se ON p.series_id=se.id LEFT JOIN sotf_prog_rating rating ON p.id=rating.prog_id LEFT JOIN (SELECT sum(visits) AS visits, sum(listens) AS listens, sum(downloads) AS downloads, prog_id FROM sotf_stats GROUP BY prog_id) AS stats ON stats.prog_id=p.id, sotf_user_permissions u WHERE u.user_id = '$user->id' AND u.object_id=p.id ORDER BY p.entry_date DESC";
    $plist = $db->getAll($sql);
    /*
    foreach($plist as $item) {
      $retval[] = new sotf_Programme($item['id'], $item);
    }*/
    return $plist;
  }

  function internalSearch($text, $language) {
    $langCond = '';
    if($language && $language != 'any_language') {
      $language = sotf_Utils::clean($language);
      $sql .= " AND language='$language' ";
    }
    //    $sql = "FROM sotf_programmes p, sotf_object_roles o, sotf_contacts c WHERE p.id=o.object_id AND o.contact_id=c.id AND p.published='t' ";
    //$sql .= " AND (p.title ~* '$text' OR p.keywords ~* '$text' OR p.abstract ~* '$text' OR c.name ~* '$text' OR p.spatial_coverage ~* '$text') ";
    $query = "SELECT distinct programmes.* FROM ( SELECT sotf_programmes.*, sotf_stations.name as station, sotf_series.title as seriestitle, sotf_series.description as seriesdescription FROM sotf_programmes LEFT JOIN sotf_stations ON sotf_programmes.station_id = sotf_stations.id LEFT JOIN sotf_series ON sotf_programmes.series_id = sotf_series.id) as programmes WHERE published = 't' $langCond AND ( (coalesce(title,'') ~* '.*$text.*' OR coalesce(alternative_title,'') ~* '.*$text.*' OR coalesce(episode_title,'') ~* '.*$text.*') OR coalesce(abstract,'') ~* '.*$text.*' OR coalesce(spatial_coverage,'') ~* '.*$text.*' OR coalesce(keywords,'') ~* '.*$text.*' OR id in (SELECT sotf_object_roles.object_id as id FROM sotf_object_roles WHERE sotf_object_roles.contact_id = sotf_contacts.id AND ( coalesce(sotf_contacts.name,'') ~* '.*$text.*' OR coalesce(sotf_contacts.alias,'') ~* '.*$text.*' OR coalesce(sotf_contacts.acronym,'') ~* '.*$text.*')) ) ORDER BY entry_date, title";
    return $query;
  }

  function countSearch($text, $language) {
    global $db;
    $sql = "FROM sotf_programmes p, sotf_object_roles o, sotf_contacts c WHERE p.id=o.object_id AND o.contact_id=c.id AND p.published='t' ";
    $sql .= " AND (p.title ~* '$text' OR p.keywords ~* '$text' OR p.abstract ~* '$text' OR c.name ~* '$text' OR p.spatial_coverage ~* '$text') ";
    if($language && $language != 'any_language') {
      $language = sotf_Utils::clean($language);
      $sql .= " AND language='$language' ";
    }
    $sql .= " GROUP BY p.id ORDER BY p.entry_date DESC ";
    return $db->getOne("SELECT count(*) FROM (" . sotf_Programme::internalSearch($text, $language) . ") AS foo"); 
  }

  function simpleSearch($text, $language, $from, $count) {
    global $db;
    $res = $db->limitQuery(sotf_Programme::internalSearch($text, $language), $from, $count);
    if(DB::isError($res))
      raiseError($res->getMessage());
    while (DB_OK === $res->fetchInto($row)) {
      //debug("row", $row['title']);
      $list[] = $row;
      if(!empty($row['icon'])) {
        sotf_Programme::cacheIcon($row['id'], $db->unescape_bytea($row['icon']));
      }
    }
    return $list;
  }

}

?>
