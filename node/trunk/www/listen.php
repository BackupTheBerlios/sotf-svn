<?php
require("init.inc.php");

$id = sotf_Utils::getParameter('id');
$fileid = sotf_Utils::getParameter('fileid');

if(empty($id)) {
  raiseError("Missing parameters!");
}

$prg = new sotf_Programme($id);

$playlist = new sotf_Playlist();

$playlist->addProg($prg, $fileid);

$playlist->startStreaming();

$playlist->sendRemotePlaylist();

$page->logRequest();

?>