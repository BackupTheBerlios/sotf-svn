<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: Andr�s Micsik, M�t� Pataki, Tam�s D�ri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

if(sotf_Utils::getParameter('reconnect')) {
  $playlist = new sotf_Playlist();
  $playlist->sendMyRemotePlaylist();
  $page->logRequest();
  exit;
}

if(sotf_Utils::getParameter('stop')) {
  $playlist = new sotf_Playlist();
  $playlist->stopMyStream();
  $page->redirect(myGetenv('HTTP_REFERER'));
  exit;
}

$id = sotf_Utils::getParameter('id');
$fileid = sotf_Utils::getParameter('fileid');

if(!$id) {
  sotf_Utils::collectPathinfoParams();
  if(!$id)
	 $id = $pathinfoParams['id'];
  if(!$fileid)
	 $id = $pathinfoParams['fileid'];
}

if(empty($id)) {
  raiseError("Missing parameters!");
}

$prg = new sotf_Programme($id);

if(!$prg->isLocal()) {
  // have to send user to home node of this programme
  sotf_Node::redirectToHomeNode($prg, 'listen.php');
  exit;
}

$playlist = new sotf_Playlist();

$playlist->addProg($prg, $fileid);

$playlist->startStreaming();

// must start stream before! otherwise we don't know stream url
$playlist->sendRemotePlaylist();

$page->logRequest();

?>