<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-

/* 
 * $Id$
 *
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *				at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

define("GUID_DELIMITER", ':');
define("TRACKNAME_LENGTH", 32);

require_once($config['classdir'] . '/Tar.php');
require_once($config['classdir'] . '/unpackXML.class.php');
require_once($config['classdir'] . '/sotf_Statistics.class.php');

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
	 $this->sotf_ComplexNodeObject('sotf_programmes', $id, $data);
	 if($this->exists()) {
		$station = $this->getStation();
		if($station) {
		  $this->stationName = $station->get('name');
		}
		//debug("stationName", $this->stationName);
	 }
  }
  
  function generateGUID() {
	 $this->set('guid', $this->stationName . GUID_DELIMITER . $this->get('entry_date') . GUID_DELIMITER . $this->get('track'));
  }

  /** finds the next available track id within the station ($track may be empty) */
  function getNextAvailableTrackId() {
	global $db;

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
		  $res = $db->getOne("SELECT count(*) FROM sotf_programmes WHERE guid='$guid'");
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

  function create($stationOrSeriesId, $track='') {
	 global $db, $repository;

	 $db->begin();
	 // TODO may need some locking to get unique track id
	 $container = & $repository->getObject($stationOrSeriesId);
	 switch(get_class($container)) {
	 case 'sotf_series':
		$this->set('series_id', $container->id);
		$station = $container->getStation();
		$this->set('station_id', $station->id);
		$stationName = $station->get('name');
		break;
	 case 'sotf_station':
		$this->set('station_id', $container->id);
		$stationName = $container->get('name');
		break;
	 default:
		raiseError("Invalid station or series id!");
	 }
	 if(empty($stationName))
		raiseError("station does not exist");
	 if(empty($track))
		$track = 'prg';
	 $this->stationName = $stationName;
	 $this->set('entry_date', date('Y-m-d'));
	 $this->set('track', sotf_Utils::makeValidName($track, 32));
	 $this->getNextAvailableTrackId();
	 if(parent::create()) { // this will also create the required directories via setMetadataFile !!
		debug("created new programme", $this->get['guid']);
		$this->saveMetadataFile();
		$db->commit();
		return true;
	 }
	 $db->rollback();
	 raiseError("Could not create new programme");
  }

  function getStation() {
	 if(!$this->station)
		$this->station = $this->getObject($this->get('station_id'));
	 return $this->station;
  }

  function getSeries() {
	 $sid = $this->get('series_id');
	 debug('soid',$sid);
	 if(!empty($sid))
		return $this->getObject($sid);
	 else 
		return NULL;
  }

  function getAssociatedObjects($tableName, $orderBy) {
	 global $db;

	 $objects = $db->getAll("SELECT * FROM $tableName WHERE prog_id='$this->id' ORDER BY $orderBy");
	 return $objects;
  }

  /** returns the directory where programme files are stored */
  function getDir() {
	global $repository;
	 return $repository->rootdir . '/' . $this->stationName . '/' . $this->data['entry_date'] . '/' . $this->data['track'];
  }

  /** returns the directory where metadata/jingles/icons are stored */
  function getMetaDir() {
	 return $this->getDir();
  }

  /** returns directory where audio files are stored for the programme */
  function getAudioDir() {
	 return $this->getDir() . '/audio';
  }

  /** returns directory where other files are stored for the programme */
  function getOtherFilesDir() {
	 return $this->getDir() . '/files';
  }

  /** private
	  Checks and creates subdirs if necessary.
	*/
  function checkDirs() {
	global $repository;

	 $station = $this->stationName;
	 $dir = $repository->rootdir . '/' . $station;
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

  function getFilePath($file) {
	 if(!$this->isLocal())
		raiseError('no_such_file');
	 if($file->tablename == 'sotf_media_files' && $file->get('main_content')=='t') {
		return $this->getAudioDir() . '/' . $file->get('filename');
	 } else {
		return $this->getOtherFilesDir() . '/' . $file->get('filename');
	 }
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

	/************************************************
	 *      METADATA
	 ************************************************/

  function saveMetadataFile() {
	 global $permissions;

	 $name = get_class($this);
	 $name = str_replace("sotf_", "", $name);
	 $xml = "<$name>";
	 $xml .= sotf_Utils::writeXML('data', $this->data, 1);
	 $roles = $this->getRoles();
	 $xml .= sotf_Utils::writeXML('role', $roles, 1);
	 $perms = $permissions->listUsersAndPermissions($this->id);
	 $xml .= sotf_Utils::writeXML('permission', $perms, 1);
	 $links = $this->getAssociatedObjects('sotf_links', 'caption');
	 $xml .= sotf_Utils::writeXML('link', $links, 1);
	 $rights = $this->getAssociatedObjects('sotf_rights', 'start_time');
	 $xml .= sotf_Utils::writeXML('right', $rights, 1);
	 $topics = $this->getTopics();
	 $xml .= sotf_Utils::writeXML('topic', $topics, 1);
	 $xml = $xml . "\n</$name>\n";
	 // TODO: save more data from other tables as well !!!!!

	 $file = $this->getDir() . '/metadump.xml';
	 debug("dumping metadata xml in", $file);
	 $fp = fopen("$file", "w");
	 fwrite($fp, $xml);
	 fclose($fp);
	 return true;
  }

	/************************************************
	 *      STATISTICS AND FEEDBACK
	 ************************************************/

  function addStat($fileId, $type) {
		sotf_Statistics::addStat($this, $fileId, $type);
  }

  function getStats() {
	 return sotf_Statistics::getGlobalStats($this->id);
  }

  /** static */
  function getFileStats() {
	 global $db;
	 return $db->getRow("SELECT sum(filesize) AS filesize, sum(play_length) AS play_length FROM sotf_media_files");
  }

  function getRefs() {
	global $db;

	 $id = $this->id;
	 $result = $db->getAll("SELECT * FROM sotf_prog_refs WHERE id='$id'" );
	 if(DB::isError($result))
		return array();
	 else
		return $result;
  }

  /************************************************
   *   QUERIES
   ************************************************/

  /** get news for index page */
  function getNewProgrammes($fromDay, $maxItems) {
	 global $config, $db;

	 $sql = "SELECT i.* FROM sotf_programmes i, sotf_stations s WHERE i.station_id = s.id AND i.published='t' AND i.entry_date >= '$fromDay' ORDER BY i.entry_date DESC";
	 $res =	$db->limitQuery($sql, 0, $maxItems);
	 if(DB::isError($res))
		raiseError($res);
	 $results = null;
	 while (DB_OK === $res->fetchInto($row)) {
		$row['icon'] = sotf_Blob::cacheIcon($row['id']);
		$results[] = $row;
	 }
	 return $results;
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
  function myProgrammes($series, $filter, $sort, $count = false) {
	 global $permissions, $db, $user;

		if(!isset($permissions->currentPermissions))
		return NULL;  // not logged in yet
	 $sql = "SELECT  s.name AS station, se.name AS series, stats.visits, stats.listens, stats.downloads, flags.flags, rating.*, p.*".
		" FROM sotf_programmes p LEFT JOIN sotf_stations s ON p.station_id = s.id".
		" LEFT JOIN sotf_series se ON p.series_id=se.id".
		" LEFT JOIN sotf_prog_rating rating ON p.id=rating.prog_id".
		" LEFT JOIN sotf_user_progs flags ON p.id=flags.prog_id AND flags.user_id='$user->id'".
		" LEFT JOIN sotf_prog_stats stats ON stats.prog_id=p.id " .
      ", sotf_user_permissions u".
		" WHERE u.user_id = '$user->id' AND u.object_id=p.id";
	 if ($series != "allseries") $sql .= " AND p.series_id='$series'";
	 if ($filter == "all") ;
	 elseif ($filter == "published") $sql .= " AND p.published='t'";
	 elseif ($filter == "unpublished") $sql .= " AND p.published='f'";
	 else $sql .= " AND flags = '$filter'";
	 if ($sort) $sql .= " ORDER BY $sort";
	 
	 if ($count) return $db->getOne("SELECT count(*) FROM ($sql) as a");
	 $plist = $db->getAll($sql);
	 /*
	 foreach($plist as $item) {
		$retval[] = new sotf_Programme($item['id'], $item);
	 }*/
	 return $plist;
  }

  /************************************************
   *  FILE MANAGEMENT
   ************************************************/

  /** Returns an array containing info about the available audio files. */
  function listAudioFiles($mainContent = 'true') {
	 global $db;

	 $objects = $db->getAll("SELECT * FROM sotf_media_files WHERE prog_id='$this->id' AND main_content='$mainContent' ORDER BY filename");
	 return $objects;
  }

  /** Returns an array containing info about the available other files. */
  function listOtherFiles() {
	global $db;

	 $objects = $db->getAll("SELECT * FROM sotf_other_files WHERE prog_id='$this->id' ORDER BY filename");
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
	 // return first free to stream
	 while(list(,$f) = each($files)) {
		if($f['stream_access']=='t')
		  return $f['id'];
	 }
	 return '';
  }

  function setAudio($filename, $copy=false) {
	 global $page;

	 $source = $filename;
	 if(!is_file($source))
		raiseError("no such file: $source");
	 $srcFile = new sotf_AudioFile($source);
	 $target = $this->getAudioDir() .  '/' . $this->get('track') . '_' . $srcFile->getFormatFilename();
	 if(!$srcFile->isAudio())
		raiseError("this is not an audio file");
	 //if(is_file($target)) {
	 //	raiseError($page->getlocalized('format_already_present'));
	 //}
	 // check and save length
	 $length = round($srcFile->duration);
	 if(!$this->get('length')) {
		$this->set('length', $length);
		$this->update();
	 } else {
		$diff = abs($this->get('length') - $length);
		if($diff > 15) { 
		  // allow for 15 sec of difference in program length
		  raiseError("audio_length_no_match");
		}
	 }
	 if($copy)
		$success = copy($source,$target);
	 else
		$success = rename($source,$target);
	 if(!$success)
		raiseError("could not copy/move $source");
	 // save file info into database
	 $this->saveFileInfo($target, true);
  }

  function setOtherFile($filename, $copy=false) {
	 global $user;

	 $source =	$filename;
	 $target = $this->getOtherFilesDir() . '/' . basename($filename);
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
	 return $this->saveFileInfo($target, false);
  }

  function saveFileInfo($filepath, $mainContent = false) {
	 global $db;

	 // convert boolean into pgsql format
	 if($mainContent)
		$mainContent = 'true';
	 else 
		$mainContent = 'false';
	 // get audio properties of file
	 $file = new sotf_AudioFile($filepath);
	 // find if this is an existing file
	 if($file->isAudio()) {
		$fileInfo = new sotf_NodeObject('sotf_media_files');
	 } else {
		$fileInfo = new sotf_NodeObject('sotf_other_files');
	 }
	 $fileInfo->set("prog_id", $this->id);
	 $fileInfo->set("filename", $file->name);
	 $fileInfo->find();
	 // save file info into database
	 if($file->isAudio()) {
		$fileInfo->set('play_length', round($file->duration));
		$fileInfo->set('type', $file->type);
		if(is_numeric($file->bitrate)) {
		  // constant bitrate
		  $fileInfo->set('kbps', round($file->bitrate));
		  $fileInfo->set('vbr', 'f');
		} else {
		  // variable bitrate
		  $fileInfo->set('kbps', round($file->average_bitrate));
		  $fileInfo->set('vbr', 't');
		}
		$fileInfo->set('format', $file->getFormatFilename());
		$fileInfo->set('main_content', $mainContent);
	 }
	 $fstat = stat($filepath);
	 $fileInfo->set('filesize', $fstat['size']);
	 $fileInfo->set('last_modified', $db->getTimestampTz($fstat['mtime']));
	 $fileInfo->set('mime_type', $file->mimetype);
	 $success = $fileInfo->save();
	 //if(!$success)
	 //	raiseError("could not write into database");
	 return $fileInfo->id;
  }

  function deleteFile($fid) {
	global $repository;

	 $table = $repository->getTable($fid);
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

  /************************************************
   *      TOPICS
   ************************************************/

  function sortTopicsByName($a, $b) {
	 return strcmp($a['name'], $b['name']);
  }

  function getTopics() {
	global $repository;
 
	 $topics = $this->getAssociatedObjects('sotf_prog_topics', 'id');
	 for($i=0; $i<count($topics); $i++) {
		$topics[$i]['name'] = $repository->getTopicName($topics[$i]['topic_id']);
	 }
	 usort($topics, array('sotf_Programme', 'sortTopicsByName'));
	 return $topics;
  }


  /************************************************
   *      XBMF import/export
   ************************************************/

  /** private: strips newlines, truncates, etc. metadata fields in XBMF */
  function normalizeText($text, $length=0) {
	 $text = str_replace("\r","", $text);
	 $text = str_replace("\n"," ", $text);
	 if($length) {
		if(strlen($text) > $length)
		  $text = substr($text, 0, $length-3) . '...';
	 }
	 return $text;
  }

  /** static: import a programme from the given XBMF archive */
  function importXBMF($fileName, $publish=false) {
	 global $db, $config, $permissions, $repository;

	 $pathToFile = $config['xbmfInDir'] . '/';
	 // create temp folder with unique name
	 $folderName = uniqid("xbmf");
	 mkdir($pathToFile . $folderName);
	
	 // untar contents of file to folder
	 $tar = new Archive_Tar($fileName, true);								// create archive handler
	 $tar->setErrorHandling(PEAR_ERROR_PRINT);								// enable error reporting
	 $result = $tar->extract($pathToFile . $folderName);			// untar contents
	 debug("untar result", $result);
	
	 //parse the xml file
	 $metaFile = $pathToFile . $folderName . "/XBMF/Metadata.xml";
	 if(!is_file($metaFile)) {
		$metaFile = $pathToFile . $folderName . "/XBMF/metadata.xml";
		if(!is_file($metaFile)) {
		  raiseError("no metadata file found in XBMF!");
		}
	 }
	 $myPack = new unpackXML($metaFile);
	
	 if(!$myPack->error){		//if the file has been found
		$metadata = $myPack->process();
	 }
		
	 if(!$metadata or $myPack->error){ //errors during import - stop execution
		sotf_Utils::delete($pathToFile . $folderName);
		echo "<font color=#FF0000><b>The import of $fileName did not succeed!</b></font>";
		return false;	//did not succeed
	 }else{
		/*
		echo "Came In: " . $myPack->encoding . "<br>";
		echo "Went Out: " . $myPack->outencoding . "<br>";
		echo "<pre>";
		print_r($metadata);
		echo "</pre>";
		*/
		//dump($metadata, "METADATA");
	 }

	 if($metadata['identifier']) {
		$prgId = sotf_Programme::getMapping($metadata['identifier']);
	 }

	 if($prgId) {
		// updating an exisiting programme
		$newPrg =  new sotf_Programme($prgId);
		$station = &$repository->getObject($newPrg->get('station_id'));
		$updatingPrg = 1;
		debug("updating existing programme", $prgId);
	 } else {
		// a new programme
		$newPrg = new sotf_Programme();
		// TODO: by default I put the programme into the first station
		$stId = $db->getOne("SELECT id FROM sotf_stations ORDER BY id");
		
		$station = &$repository->getObject($stId);
		$track = $metadata['title'];
		debug("create with track", $track);
		$newPrg->create($station->id, $track);
		sotf_Programme::addMapping($metadata['identifier'], $newPrg->id);
	 }

	 $newPrg->set('foreign_id', $metadata['identifier']);

	 // series
	 if($metadata['series'] && $metadata['series']['id']) {
		$seriesId = sotf_Programme::getMapping($metadata['series']['id']);
		if($seriesId) {
		  $newPrg->set('series_id', $seriesId);
		  $series = &$repository->getObject($seriesId);
		} else {
		  $newSeries = 1;
		  $series = new sotf_Series();
		  $series->set('station_id', $station->id);
		}
		$series->set('name', $metadata['series']['title']);
		$series->set('description', $metadata['series']['title']);
		if($series->exists()) {
		  $series->update();
		} else {
		  $series->create();
		  sotf_Programme::addMapping($metadata['series']['id'], $series->id);
		}
	 }

	 // permissions
	 foreach(array($metadata['owner'], $metadata['publishedby']) as $foreignUser) {
		if(is_array($foreignUser)) {
		  $userId = sotf_User::getUserid($foreignUser['login']);
		  if($userId) {
			 if($permissions->hasPermission($station->id, 'add_prog') ||
				 ($series && $permissions->hasPermission($series->id, 'add_prog'))) {
				// add permission for user
				$permissions->addPermission($newPrg->id, $userId, 'admin');
				$admins[] = $userId;
			 }
		  }
		}
	 }
	 // if we did not get permission info, add permissions for all station/series admins
	 if(empty($admins)) {
		if($series)
		  $admins1 = $permissions->listUsersWithPermission($series->id, 'admin');
		if(!$admins1)
		  $admins1 = $permissions->listUsersWithPermission($station->id, 'admin');
		while(list(, $admin) = each($admins1)) {
		  $admins[] = $admin['id'];
		  $permissions->addPermission($newPrg->id, $admin['id'], 'admin');
		}
	 }
	 // now create permissions
	 while(list(, $adminId) = each($admins)) {
		$permissions->addPermission($newPrg->id, $adminId, 'admin');
		if($newSeries)
		  $permissions->addPermission($series->id, $adminId, 'admin');
	 }

	 /*
	  * PART 2.1 - Move the audio data to the specified station folder
	  */
	 
	 // insert audio
	 $dirPath = $pathToFile . $folderName . "/XBMF/audio";
	 $dir = dir($dirPath);
	 while($entry = $dir->read()) {
		if ($entry != "." && $entry != "..") {
		  $currentFile = $dirPath . "/" . $entry;
		  if (!is_dir($currentFile)) {
			 if(is_file($targetFile))
				 $newPrg->setAudio($currentFile, true);
			 //break;
		  }
		}
	 }
	 $dir->close();
	 // TODO: convert missing formats!

	 // insert other files
	 $dirPath = $pathToFile . $folderName . "/XBMF/files";
	 $dir = dir($dirPath);
	 while($entry = $dir->read()) {
		if ($entry != "." && $entry != "..") {
		  $currentFile = $dirPath . "/" .$entry;
		  if (!is_dir($currentFile)) {
			 $id = $newPrg->setOtherFile($currentFile, true);
			 if($id) {
				$fileInfo = &$repository->getObject($id);
				$fileInfo->set('public_access', 't');
				$fileInfo->update();
			 }
		  }
		}
	 }
	 $dir->close();

	 // insert icon
	 $logoFile = $pathToFile . $folderName . "/icon.png";
	 if(is_readable($logoFile)) {
		$newPrg->setIcon($logoFile);
	 }

	 /*
	  * PART 2.2 - Insert all the relevant data from the xml file into the database
	  */

	 // basic metadata
	 $newPrg->set('title', sotf_Programme::normalizeText($metadata['title'],255));
	 $newPrg->set('alternative_title', sotf_Programme::normalizeText($metadata['alternative'],255));
	 $newPrg->set('episode_sequence', sotf_Programme::normalizeText($metadata['episodesequence']));
	 $newPrg->set('abstract', sotf_Programme::normalizeText($metadata['description']));
	 
	 $newPrg->set("production_date", date('Y-m-d', strtotime($metadata['created'])));
	 $newPrg->set("broadcast_date", date('Y-m-d', strtotime($metadata['issued'])));
	 $newPrg->set("modify_date", date('Y-m-d', strtotime($metadata['modified'])));

	 $newPrg->set('language', $metadata['language']);
	 if($metadata['language']=='German')
		$newPrg->set('language','de');
	 if($metadata['language']=='English')
		$newPrg->set('language','en');

	 $newPrg->update();
		
	 // topic
	 if($metadata['topic'])
		$repository->addToTopic($newPrg->id, $metadata['topic']);
	 // genre
	 if(is_numeric($metadata['genre']))
		$newPrg->set('genre_id', $metadata['genre']);
	 else
		logError("invalid genre id: " . $metadata['genre']);

	 // rights
	 $rights = new sotf_NodeObject("sotf_rights");
	 $rights->set('prog_id', $newPrg->id);
	 $rights->set('rights_text', $metadata['rights']);
	 $rights->find();
	 $rights->save();

	 // contacts
	 //$role = 21; // Other
		
	 foreach($metadata['publisher'] as $contact) {
		$role = 23; // Publisher
		$id = sotf_Programme::importContact($contact, $role, $newPrg->id, $admins);
	 }
	 foreach($metadata['creator'] as $contact) {
		$role = 22; // Creator
		$id = sotf_Programme::importContact($contact, $role, $newPrg->id, $admins);
	 }
		
	 if(is_array($metadata['contributor'])){
		foreach($metadata['contributor'] as $contact) {
		  $role = 24; // Contributor
		  $id = sotf_Programme::importContact($contact, $role, $newPrg->id, $admins);
		}
	 }
	 
	 /*
	  * PART 2.3 - Remove (unlink) the xbmf file and the temp dir
	  */
		
	 //publish if needed
	 if($publish){
		$newPrg->publish();
	 }
	 
	 sotf_Utils::delete($pathToFile . $folderName);
	 //unlink($fileName);
		
	 return $newPrg->id;
  }//end func

  /** static: create contact record from metadata */
  function importContact($contactData, $contactRole, $prgId, $admins) {
	 global $permissions, $repository, $config;

	 // find out what should go into the 'name' field
	 if($contactData['type']=='organisation') {
		$name = $contactData['organizationname'];
	 } elseif($contactData['type']=='individual') {
		$name = $contactData['firstname'] . ' ' . $contactData['lastname'];
	 } else {
		logError("unknown type of contact: " . $contactData['type']);
		return null;
	 }

	 // if not exists, create new contact
	 $id = sotf_Contact::findByNameLocal($name);
	 if(!$id) {
		$contact = new sotf_Contact();
		$status = $contact->create($name);
		if(!$status) {
		  //$page->addStatusMsg('contact_create_failed');
		  return null;
		}
		// add permissions for all station admins (??)
		while(list(, $adminId) = each($admins)) {
		  $permissions->addPermission($contact->id, $adminId, 'admin');
		}
	 } else {
		$contact = $repository->getObject($id);
	 }

	 //debug("contactData", $contactData);
	 // set/update contact data
	 $contact->set('acronym', $contactData['organizationacronym']);
	 $contact->set('alias', $contactData['alias']);
	 $contact->set('url', $contactData['uri']);
	 $contact->set('email', $contactData['email']);
	 $contact->set('address', $contactData['address']);
	 $contact->update();
	 // fetch logo from url and store
	 if(!empty($contactData['logo'])) {
		$url = $contactData['logo'];
		if ($handle = fopen($url,'rb')) {
		  $contents = "";
		  do {
			 $data = fread ($handle, 100000);
			 if (strlen($data) == 0) {
				break;
			 }
			 //debug("received", strlen($data));
			 $contents .= $data;
		  } while(0);
		  fclose($handle);
		  $tmpFile = tempnam($config['xbmfInDir'], 'logo_u');
		  debug("received logo from", $url);
		  sotf_Utils::save($tmpFile, $contents);
		  $contact->setIcon($tmpFile);
		  unlink($tmpFile);
		} else {
		  logError("Could not fetch icon from $url");
		}
	 }

	 // determine role
	 if($contactData['role']) {
		$language = 'en'; // for now
		$rid = $repository->getRoleId($contactData['role'], $language);
		if($rid)
		  $contactRole = $rid;
	 }
	 // create role
	 if(!sotf_ComplexNodeObject::findRole($prgId, $contact->id, $contactRole)) {
		$role = new sotf_NodeObject("sotf_object_roles");
		$role->set('object_id', $prgId);
		$role->set('contact_id', $contact->id);
		$role->set('role_id', $contactRole);
		$role->create();
	 }

	 return $contact->id;
  }

  /** private */
  function getMapping($foreignId) {
	 global $db;
	 return $db->getOne("SELECT id_at_node FROM sotf_station_mappings WHERE id_at_station='$foreignId'");
  }

  /** private */
  function addMapping($foreignId, $localId) {
	 global $db;
	 return $db->query("INSERT INTO sotf_station_mappings (id_at_station,id_at_node) VALUES('$foreignId', '$localId')");
  }



}

?>