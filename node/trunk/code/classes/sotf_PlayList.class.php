<?php  //-*- tab-width: 3; indent-tabs-mode: 1; -*-

require_once($config['classdir'] . "/rpc_Utils.class.php");

class sotf_Playlist {

  var $audioFiles = array();
  var $totalLength;
  var $localPlaylist;
  var $streamName;
  var $tmpId;
  var $mountPoint;
  var $url;
  var $streamId;
  var $name;

  function add($item) {
	 global $config;

	 if($item['url']) {
		// this is a remote file
		if($config['httpStreaming']) {
		  $lines = file($item['url']);
		  foreach($lines as $line) {
			 if(!empty($line))
				$this->audioFiles[] = array('url' => $line);
		  }
		} else {
		  // nothing to do
		  // not tested for Tamburine
		}
	 } else {
		// this is a local file

		// CHANGED BY BUDDHAFLY 06-05-12
		$getID3 = new getID3();
		$mp3info = $getID3->analyze($item['path']);
		getid3_lib::CopyTagsToComments($mp3info);
		//$mp3info = GetAllFileInfo($item['path']);
		//debug('mp3info', $mp3info);
		$bitrate = (string) $mp3info['audio']['bitrate'];
		if(!$bitrate)
		  raiseError("Could not determine bitrate, maybe this audio is temporarily unavailable");
		$item['bitrate'] = $bitrate;
		
		if($config['httpStreaming']) {
		  //$tmpFileName = 'au_' . $item['id'] . '_' . ($item['name'] ? $item['name'] : basename($item['path']));
		  $tmpFileName = 'au_' . $item['id'] . '_' . basename($item['path']);
		  $tmpFile = $config['tmpDir'] . "/$tmpFileName";
		  $file = @readlink($tmpFile);
		  if($file) {
			 if(!is_readable($file)) {
				logError("Bad symlink: $tmpFile to $file");
				unlink($tmpFile);
				$file = false;
			 }
		  }
		  if(!$file) {
			 if(!symlink($item['path'], $tmpFile)) {
				raiseError("symlink failed in tmp dir");
			 }
		  }
		  $item['url'] = $config['tmpUrl'] . "/$tmpFileName";
		}
		$this->totalLength += $mp3info["playtime_seconds"];
		$this->audioFiles[] = $item;
	 }
  }

  function addJingle($obj) {
	 $jfile = $obj->getJingle();
	 if($jfile)
		$this->add(array('id' => $obj->id, 'path' => $jfile, 'jingle' => 1, 'name' => '_jingle'));
	 else
		raiseError("error while playing jingle");
  }

  function addProg($prg, $fileid='') {
	 if(empty($fileid)) {
		// find a file to listen
		$fileid = $prg->selectFileToListen();
		if(!$fileid)
		  raiseError("no_file_to_listen");
	 }

	 $file = new sotf_NodeObject("sotf_media_files", $fileid);

	 if(!$prg->isLocal()) {
		$node = sotf_Node::getNodeById($file->getNodeId());
		$path = $node->get('url') . "/listen.php?id=" . $prg->id . "&fileid=" . $file->id;
		$this->add(array('path' => $path, 'url'=> $path));
		return;
	 }
	 
	 if($prg->get('published') != 't' || $file->get('stream_access') != 't') {
		raiseError("no_listen_access");
	 }

	 $filepath = $prg->getFilePath($file);
	 $index = sotf_AudioCheck::getRequestIndex(new sotf_AudioFile($filepath));
	 debug("audio index", $index);
	 if(!$index)
		$index = '0';

	 // add jingle for station (if exists)
	 $station = $prg->getStation();
	 $jfile = $station->getJingle($index);
	 if($jfile)
		$this->add(array('id' => $station->id, 'path' => $jfile, 'jingle' => 1, 'name' => '_station_jingle'));

	 // add jingle for series (if exists)
	 $series = $prg->getSeries();
	 if($series) {
		$jfile = $series->getJingle($index);
		if($jfile)
		  $this->add(array('id' => $series->id, 'path' => $jfile, 'jingle' => 1, 'name' => '_series_jingle'));
	 }

	 // add program file
	 $filepath = $prg->getFilePath($file);
	 $this->add(array('id' => $prg->id, 'path' => $filepath, 'name' => urlencode($prg->get('title')), 'length' => $prg->get('length') ));
	 
	 // temp: set title
	 $title = $prg->get("title");
	 $title = preg_replace('/\s+/', '_', $title);
	 $this->name = urlencode($title);

	 // save stats
	 $prg->addStat($file->get('id'), 'listens');
  }

  function getTmpId() {
	 global $user, $page;
	 if(!$this->tmpId) {
		if($page->loggedIn()) {
		  //$userid = urlencode(preg_replace('/\s+/', '_', $user->name));
		  $userid = 'u' . $user->id;
		} else {
		  $userid = 'guest';
		}
		$this->tmpId = $userid . '_' . time();
	 }
	 return $this->tmpId;
  }

  function getMountPoint() {
	 global $user, $page;
	 if(!$this->mountPoint) {
		//$userid = urlencode(preg_replace('/\s+/', '_', $user->name));
		//if(!$userid)
		//  $userid = 'guest';
		if($this->name) {
		  $this->mountPoint = substr($this->name, 0, 20);
		  $this->mountPoint .= '_' . date('i-s');
		} else {
		  $this->mountPoint = $page->getlocalized('playlist_name');
		  $this->mountPoint .= '_' . date('i_s');
		}
		$this->mountPoint = preg_replace('/\s+/', '_', $this->mountPoint);
		$this->mountPoint = preg_replace('/_+/', '_', $this->mountPoint);
	 }
	 return $this->mountPoint;
  }

  /** Saves the local playlist */
  function makeLocalPlaylist() {
	 global $config, $user, $db;

	 if(count($this->audioFiles) == 0)
		raiseError("playlist_empty");

	 // clear old playlists
	 $userid = $user->id;
	 if(!$userid)
		$userid = 'guest';
	 $dir = dir($config['tmpDir']);
    while($entry = $dir->read()) {
      if (preg_match("/^pl_$userid/", $entry)) {
        if(!unlink($config['tmpDir'] . "/" . $entry))
			 logError("Could not delete playlist: $entry");
		}
    }

	 // write new playlist
	 $tmpfile = $config['tmpDir'] . '/pl_' . $this->getTmpId() . '.m3u';
	 $fp = fopen($tmpfile,'wb');
	 if(!$fp)
		raiseError("Could not write to playlist file: $tmpfile");
    fwrite($fp, "#EXTM3U\n");
	 debug('AUDIO_FILES', $this->audioFiles);
    reset($this->audioFiles);
    while(list(,$audioFile) = each($this->audioFiles)) {
      $l=1;
      if($audioFile['length'])
        $l = $audioFile['length'];
      $name = urldecode($audioFile['name']);
      fwrite($fp, "#EXTINF:$l,$name\n");
 		if($config['httpStreaming']) {
		  fwrite($fp, $audioFile['url'] . "\n");
		} else {
		  fwrite($fp, $audioFile['path'] . "\n");
		}
	 }
	 fclose($fp);

	 $this->localPlaylist = $tmpfile;
	 return $tmpfile;
  }

  function startStreaming() {
	 global $config, $page, $db;

	 if($config['httpStreaming']) {
		$this->makeLocalPlaylist();
		$this->url = $config['tmpUrl'] . '/pl_' . $this->getTmpId() . '.m3u';
		return;
	 }
	 
	 // check if the stream has started already (Win+IE+Media player)
	 $urls = $db->getCol("SELECT url FROM sotf_streams WHERE host='" . getHostName() . "' AND started < CURRENT_TIMESTAMP AND started > CURRENT_TIMESTAMP - interval '15 seconds'");
	 if(count($urls) == 1) {
		debug("found url for Win Explorer", $urls[0]);
		$this->url = $urls[0];
		// found stream so we can return
		return;
	 }
  
	 $this->stopMyStream();

	 if($config['tamburineURL']) {
		// streaming with tamburine + XML-RPC

		// playlist
		reset($this->audioFiles);
		while(list(,$audioFile) = each($this->audioFiles)) {
		  $songs[] = $audioFile['path'];
		}

		$rpc = new rpc_Utils;
		//$rpc->debug = true;
      $response = $rpc->callTamburine('setpls', $songs);
		if(is_null($response)) {
		  raiseError("no reply from tamburine server");
		} else {
		  $this->url = $response[2];
		  $this->streamId = $response[1];
		}
		if(!$this->url)
		  raiseError("Could not find mount point for stream!");

	 } elseif($config['tamburineCMD']) {
		// streaming with tbrcmd

		if(!$this->localPlaylist) 
		  $this->makeLocalPlaylist();

		$cmd = $config['tamburineCMD'] . " setpls " . $this->localPlaylist . " 2>&1";
		exec($cmd, $output, $retval);
		debug("cmd", $cmd);
		//debug("output", $output);
		//debug("retval", $retval);

		$lines = array_values(preg_grep('/Fatal Error:/', $output));
		if(count($lines) > 0) {
		  raiseError( join(", ", $lines));
		  // TODO: restart tamburine (??)
		}

		//$lines = preg_grep('/Stream\[(\d+)\]\s+spawned on (\S+)/', $output);
		foreach($output as $line) {
		  if(preg_match('/Stream\[(\d+)\]\s+spawned on (\S+)/', $line, $mm)) {
			 $this->streamId = $mm[1];
			 $this->url = $mm[2];
			 break;
		  }
		}
		if(!$this->url)
		  raiseError("Could not find mount point for stream!");
		
	 } else {
		// command-line streaming

		if(!$this->localPlaylist) 
		  $this->makeLocalPlaylist();
		
		$this->url = 'http://' . $config['iceServer'] . ':' . $config['icePort'] . '/' . $this->getMountPoint() . "\n";
		//$url = preg_replace('/^.*\/repository/', 'http://sotf2.dsd.sztaki.hu/node/repository', $filepath);
		
		// TODO: calculate bitrate from all files...
		$bitrate = $this->audioFiles[0]['bitrate'];
		if(!$bitrate)
		  $bitrate = 24;
		
		$this->cmdStart($this->localPlaylist, $this->getMountPoint(), $bitrate);
		//$this->cmdStart2($bitrate);

	 }

	 if($this->url) {
		$streamData = array('pid' => $this->streamId,
								  'url' => $this->url,
								  'started' => $db->getTimestamp(),
								  'length' => round($this->totalLength),
								  'will_end_at' => $db->getTimestamp(time() + round($this->totalLength)),
								  'host' => getHostName(),
								  );
		debug("streamData", $streamData);
		$_SESSION['stream'] = $streamData;
		$obj = new sotf_Object('sotf_streams');
		$obj->setAll($streamData);
		$obj->create();

		// TODO wait until stream really starts
		sleep(3);
	 }

  }

  function stopStream($streamData) {
	 global $config, $db;

	 debug("stop stream", $streamData);

	 if($config['httpStreaming']) {	 
		// nothing to be done
	 } elseif($config['tamburineURL']) {
		$rpc = new rpc_Utils;
		//$rpc->debug = true;
      $response = $rpc->callTamburine('quit', $streamData['pid']);
		if(is_null($response)) {
		  raiseError("no reply from tamburine server");
		}
	 } elseif($config['tamburineCMD']) {
		// streaming with tbrcmd
		
		$cmd = $config['tamburineCMD'] . " quit " . $streamData['pid'] . " 2>&1";
		exec($cmd, $output, $retval);
		debug("cmd", $cmd);
		//debug("output", $output);
		//debug("retval", $retval);
	 }

	 $db->query("DELETE FROM sotf_streams WHERE pid='" . $streamData['pid'] . "'");
  }

  function getStreamInfo($streamData) {
	 global $config, $db;

	 if($config['httpStreaming']) {	 
		// no stream info...
		return;
	 } elseif($config['tamburineURL']) {
		$rpc = new rpc_Utils;
		//$rpc->debug = true;
      $response = $rpc->callTamburine('getpls', $streamData['pid']);
		if(is_null($response)) {
		  logError("no reply from tamburine server");
		} else {
		  $offset = $response[2];
		  $filename = $response[2+$offset];
		  //debug("response", $response);
		  //debug("fname", $filename);
		}
	 } elseif($config['tamburineCMD']) {
		// streaming with tbrcmd
		$cmd = $config['tamburineCMD'] . " getpls " . $streamData['pid'] . " 2>&1";
		exec($cmd, $output, $retval);
		debug("cmd", $cmd);
		//debug("output", $output);
		//debug("retval", $retval);
		foreach($output as $line) {
		  if(preg_match('/\-\>(\/\S+)\s*$/', $line, $mm)) {
			 $filename = $mm[1];
			 break;
		  }
		}
	 }
	 if($filename) {
		$prg = sotf_Programme::getPrgFromFilename($filename);
		if(is_object($prg)) {
		  return array('name' => $prg->get('title'),
							'url' => $config['localPrefix'] . "/get.php/" . $prg->id);
		} else {
		  return array('name' => $prg);
		}
	 } else {
		debug("Could not get stream info");
		// TODO: stop stream!
	 }
  }

  function stopMyStream() {
	 if($_SESSION['stream']) {
		$this->stopStream($_SESSION['stream']);
		unset($_SESSION['stream']);
		sleep(2);
	 }
  }

  function stopOldStreams() {
	 global $config, $db;

	 $dataset = $db->getAll("SELECT * FROM sotf_streams WHERE will_end_at < CURRENT_DATE + interval '10 minutes'");

	 foreach($dataset as $data) {
		$this->stopStream($data);
	 }
  }

  function sendMyRemotePlaylist() {
	 if($_SESSION['stream']) {
		$this->url = $_SESSION['stream']['url'];
	 }
	 $this->sendRemotePlaylist();
  }

  function sendRemotePlaylist() {
	 /*
	 // send playlist to client
	 header("Content-type: audio/x-mpegurl\n");
	 //header("Content-transfer-encoding: binary\n");
	 //header("Content-length: " . strlen($url) . "\n");
	
	 // send playlist
	 echo $this->url;
	 */
	 header("Location: " .  $this->url);
	 debug("sent playlist", $this->url);
  }

  function cmdStart($playlist, $name, $bitrate) {
	 global $config;

	 $url = $config['localPrefix'] . "/startStream.php?pl=$playlist&n=$name&br=$bitrate";

	 $server='localhost';
	 $port= myGetenv('SERVER_PORT');
	 $host = myGetenv('SERVER_NAME');
	 debug("host", $host);
	 debug("port", $port);
	 
	 $op= "GET $url HTTP/1.1
Accept: */*
Host: $host
User-Agent: PHP

";

	 $fp=fsockopen($host, $port, $errno, $errstr);
	 if (!$fp)
		{
		  raiseError('Streaming error: Connect error');
		}

	 if (!fputs($fp, $op, strlen($op)))
		{
		  raiseError('Streaming error: Write error');
		}

  }

  /** old alternative, does not work under Windows and some Unices */
  function cmdStart2($bitrate) {
	 global $config;

	 $mystreamCmd = str_replace('__PLAYLIST__', $this->localPlaylist , $config['streamCmd']);
	 $mystreamCmd = str_replace('__NAME__', $this->getTmpId(), $mystreamCmd);
	 $mystreamCmd = str_replace('__BITRATE__', $bitrate, $mystreamCmd);
		
	 debug("starting stream with cmd", $mystreamCmd);
		
	 system($mystreamCmd);
	 debug("streaming process detached");
  }

}
?>
