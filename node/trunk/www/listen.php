<?php
require("init.inc.php");

$id = sotf_Utils::getParameter('id');
$fileid = sotf_Utils::getParameter('fileid');

if(empty($id) || empty($fileid)) {
  raiseError("Missing parameters!");
}

$prg = new sotf_Programme($id);
$file = new sotf_NodeObject("sotf_media_files", $fileid);

if(!$prg->isLocal()) {
  raiseError("Currently you can listen only to programmes of local stations");
}

if($prg->get('published') != 't' || $file->get('stream_access') != 't') {
  raiseError("no_listen_access");
}

$filepath = $prg->getFilePath($file);
$tmpfile = $tmpdir . "/$id_$fileid.m3u";
$name = "$id_$fileid";
$url = 'http://' . $iceServer . ':' . $icePort . '/' . $name . "\n";
debug("file", $filepath);

if (!is_file($filepath)) {
     raiseError("no_such_file");
}

// prepare playlist for streaming into icecast
$fp = fopen($tmpfile,'w');
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
debug("Cmd", $mystreamCmd);
debug("Url", $url);
$res = exec($mystreamCmd);
//debug("Cmd output", $res);

// send playlist to client
header("Content-type: audio/x-mpegurl\n");
//header("Content-transfer-encoding: binary\n");
header("Content-length: " . strlen($url) . "\n");
	
// send playlist
echo $url;

// save stats
$prg->addStat($file->get('filename'), 'listens');

?>