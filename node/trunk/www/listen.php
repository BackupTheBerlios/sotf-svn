<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: Andr�s Micsik, M�t� Pataki, Tam�s D�ri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

$id = sotf_Utils::getParameter('id');
$fileid = sotf_Utils::getParameter('fileid');

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

$playlist->stopMyStream();

$playlist->addProg($prg, $fileid);

$playlist->startStreaming();

// TODO wait until stream really starts
sleep(2);

// must start stream before! otherwise we don't know stream url
$playlist->sendRemotePlaylist();

$page->logRequest();

?>