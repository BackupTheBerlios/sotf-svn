<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
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
$jingle = sotf_Utils::getParameter('jingle');

if(empty($id)) {
  raiseError("Missing parameters!", 'id');
}

$playlist = new sotf_Playlist();

if($jingle) {
  // play the jingle of station/series
  $obj = $repository->getObject($id);
  if(!$obj)
	 raiseError("no_such_object", $id);
  if(!$obj->isLocal()) {
	 // have to send user to home node of this programme
	 sotf_Node::redirectToHomeNode($obj, 'listen.php');
	 exit;
  }
  $playlist->addJingle($obj);
} else {
  // add normal programme 
  $prg = $repository->getObject($id);
  if(!$prg)
	 raiseError("no_such_object", $id);

  if(!$prg->isLocal()) {
	 // have to send user to home node of this programme
	 sotf_Node::redirectToHomeNode($prg, 'listen.php');
	 exit;
  }
  
  $playlist->addProg($prg, $fileid);
}
  
$playlist->startStreaming();

// must start stream before! otherwise we don't know stream url
$playlist->sendRemotePlaylist();

$page->logRequest();

?>