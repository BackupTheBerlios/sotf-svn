<?php
require("init.inc.php");

$id = sotf_Utils::getParameter('id');
$fileid = sotf_Utils::getParameter('fileid');

if(empty($id)) {
  raiseError("Missing parameters!");
}

$prg = new sotf_Programme($id);

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

$filepath = $prg->getFilePath($file);
$tmpfile = $tmpdir . "/$id_$fileid.m3u";
//$name = "$id_$fileid";
$name = "$id_" . time();
$url = 'http://' . $iceServer . ':' . $icePort . '/' . $name . "\n";
debug("file", $filepath);

//$url = preg_replace('/^.*\/repository/', 'http://sotf2.dsd.sztaki.hu/node/repository', $filepath);

if (!is_file($filepath)) {
     raiseError("no_such_file");
}

// prepare playlist for streaming into icecast
$fp = fopen($tmpfile,'wb');
/* TODO: add jingle
			$jinglefile = $repo->getStationJingle($station);
			if (!is_object($jinglefile))
			{
				fwrite($fp,$jinglefile . "\n");
			}
*/
fwrite($fp,$filepath . "\n");
fclose($fp);

// prepare streaming command
$mp3info = GetAllMP3info($filepath);
$bitrate = (string) $mp3info['mpeg']['audio']['bitrate'];

$mystreamCmd = str_replace('__PLAYLIST__', $tmpfile, $streamCmd);
$mystreamCmd = str_replace('__NAME__', $name, $mystreamCmd);
$mystreamCmd = str_replace('__BITRATE__', $bitrate, $mystreamCmd);

debug("starting stream with cmd", $mystreamCmd);

exec($mystreamCmd);
//$h = popen($mystreamCmd, 'r');
//pclose($h);

debug("sending url", $url);

//$res = exec($mystreamCmd);
//debug("Cmd output", $res);

// send playlist to client
header("Content-type: audio/x-mpegurl\n");
//header("Content-transfer-encoding: binary\n");
//header("Content-length: " . strlen($url) . "\n");
	
// send playlist
echo "$url";

// save stats
$prg->addStat($file->get('filename'), 'listens');

$page->logRequest();

?>