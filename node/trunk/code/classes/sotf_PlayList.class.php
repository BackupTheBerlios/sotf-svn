<?php  //-*- tab-width: 3; indent-tabs-mode: 1; -*-

require_once("$classdir/rpc_Utils.class.php");

class sotf_Playlist {

  var $audioFiles = array();
  var $localPlaylist;
  var $streamName;
  var $tmpId;
  var $url;

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

	 // save stats
	 $prg->addStat($file->get('filename'), 'listens');
  }

  function getTmpId() {
	 global $user;
	 if(!$this->tmpId) {
		$userid = $user->id;
		if(!$userid)
		  $userid = 'guest';
		$this->tmpId = $userid . '_' . time();
	 }
	 return $this->tmpId;
  }

  /** Saves the local playlist */
  function makeLocalPlaylist() {
	 global $tmpdir, $user;

	 if(count($this->audioFiles) == 0)
		raiseError("playlist_empty");

	 // clear old playlists
	 $userid = $user->id;
	 if(!$userid)
		$userid = 'guest';
	 $dir = dir($tmpdir);
    while($entry = $dir->read()) {
      if (preg_match("/^pl_$userid/", $entry)) {
        if(!unlink($tmpdir . "/" . $entry))
			 logError("Could not delete playlist: $entry");
		}
    }

	 // write new playlist
	 $tmpfile = $tmpdir . '/pl_' . $this->getTmpId() . '.m3u';
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
	 global $tamburineURL, $iceServer, $icePort, $tamburine, $streamCmd;

	 if(!$this->localPlaylist) 
		$this->makeLocalPlaylist();

	 if($tamburineURL) {
		// tamburine-based streaming

		if($_SESSION['playlist_id']) {
		  //TODO? kill old stream
		}

      $response = $rpc->call($tamburineURL, 'setpls', $this->localPlaylist);
		if(is_null($response)) {
		  debug("no reply from tamburine server");
		} else {
		  $this->url = $response[0];
		  $id = $response[1];
		  $_SESSION['playlist_id'] = $id;
		}

	 } else {
		// Perl-based streaming
		
		$this->url = 'http://' . $iceServer . ':' . $icePort . '/' . $this->getTmpId() . "\n";
		//$url = preg_replace('/^.*\/repository/', 'http://sotf2.dsd.sztaki.hu/node/repository', $filepath);
		
		// TODO: calculate bitrate from all files...
		$bitrate = $this->audioFiles[0]['bitrate'];
		if(!$bitrate)
		  $bitrate = 24;
		
		$mystreamCmd = str_replace('__PLAYLIST__', $this->localPlaylist , $streamCmd);
		$mystreamCmd = str_replace('__NAME__', $this->getTmpId(), $mystreamCmd);
		$mystreamCmd = str_replace('__BITRATE__', $bitrate, $mystreamCmd);
		
		debug("starting stream with cmd", $mystreamCmd);
		
		exec($mystreamCmd);
		debug("streaming process detached");
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


}
?>
