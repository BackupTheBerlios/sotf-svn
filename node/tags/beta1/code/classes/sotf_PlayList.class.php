<?php  //-*- tab-width: 3; indent-tabs-mode: 1; -*-

require_once($config['classdir'] . "/rpc_Utils.class.php");

class sotf_Playlist {

  var $audioFiles = array();
  var $localPlaylist;
  var $streamName;
  var $tmpId;
  var $url;
  var $name;

  function add($item) {
	 $mp3info = GetAllFileInfo($item['path']);
	 //debug('mp3info', $mp3info);
	 $bitrate = (string) $mp3info['audio']['bitrate'];
	 if(!$bitrate)
		raiseError("Could not determine bitrate, maybe this is not an audio file: " . $item['path']);
	 $item['bitrate'] = $bitrate;
	 $this->audioFiles[] = $item;
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
		raiseError("Currently you can listen only to programmes of local stations");
	 }
	 
	 if($prg->get('published') != 't' || $file->get('stream_access') != 't') {
		raiseError("no_listen_access");
	 }

	 // add jingle
	 $station = $prg->getStation();
	 $jfile = $station->getJingle();
	 if($jfile)
		$this->add(array('path' => $jfile, 'jingle' => 1));

	 // add program file
	 $filepath = $prg->getFilePath($file);
	 $this->add(array('path' => $filepath));
	 
	 // temp: set title
	 $this->name = urlencode($prg->get("title"));


	 // save stats
	 $prg->addStat($file->get('filename'), 'listens');
  }

  function getTmpId() {
	 global $user;
	 if(!$this->tmpId) {
		//if($this->name)
		//  $this->tmpId = $this->name;
		$userid = $user->id;
		if(!$userid)
		  $userid = 'guest';
		$this->tmpId = $this->tmpId . '_' . $userid . '_' . time();
		//$this->tmpId = $this->tmpId . '_' . time();
	 }
	 return $this->tmpId;
  }

  /** Saves the local playlist */
  function makeLocalPlaylist() {
	 global $config, $user;

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

    reset($this->audioFiles);
    while(list(,$audioFile) = each($this->audioFiles)) {
		fwrite($fp, $audioFile['path'] . "\n");
	 }
	 fclose($fp);

	 $this->localPlaylist = $tmpfile;
	 return $tmpfile;
  }

  function startStreaming() {
	 global $config, $tamburine;

	 if(!$this->localPlaylist) 
		$this->makeLocalPlaylist();

	 if($config['tamburineURL']) {
		// tamburine-based streaming

		if($_SESSION['playlist_id']) {
		  //TODO? kill old stream
		}
		
		$rpc = new rpc_Utils;
		//$rpc->debug = true;
      $response = $rpc->call($config['tamburineURL'], 'setpls', $this->localPlaylist);
		if(is_null($response)) {
		  debug("no reply from tamburine server");
		} else {
		  $this->url = $response[0];
		  $id = $response[1];
		  $_SESSION['playlist_id'] = $id;
		}

	 } else {
		// command-line streaming
		
		$this->url = 'http://' . $config['iceServer'] . ':' . $config['icePort'] . '/' . $this->getTmpId() . "\n";
		//$url = preg_replace('/^.*\/repository/', 'http://sotf2.dsd.sztaki.hu/node/repository', $filepath);
		
		// TODO: calculate bitrate from all files...
		$bitrate = $this->audioFiles[0]['bitrate'];
		if(!$bitrate)
		  $bitrate = 24;
		
		$this->cmdStart($this->localPlaylist, $this->getTmpId(), $bitrate);
		//$this->cmdStart2($bitrate);

	 }
  }

  function sendRemotePlaylist() {
	 // send playlist to client
	 header("Content-type: audio/x-mpegurl\n");
	 //header("Content-transfer-encoding: binary\n");
	 //header("Content-length: " . strlen($url) . "\n");
	
	 // send playlist
	 echo $this->url;
	
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
