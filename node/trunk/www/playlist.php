<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");
$page->forceLogin();
//sotf_Utils::getParameter("");

$playlist = new sotf_UserPlaylist();

if (sotf_Utils::getParameter("delete_selected") != "")			//delete selected button pressed
{
	$checkbox = sotf_Utils::getParameter("checkbox");
	$max =  count($checkbox);
	for($i=0; $i<$max; $i++)
	{
		$playlist->delete($checkbox[$i]);
	}
	$page->redirect("playlist.php");
}
if (sotf_Utils::getParameter("play_selected") != "")			//delete selected button pressed
{
  $pl = new sotf_Playlist();
  $checkbox = sotf_Utils::getParameter("checkbox");
  for($i=0; $i < count($checkbox); $i++) {
    $prg = new sotf_Programme($checkbox[$i]);
    $pl->addProg($prg);
  }
  $pl->startStreaming();  
  $pl->sendRemotePlaylist();
  $page->logRequest();
  exit;
}


$result = $playlist->load();

$programmes = array();
for($i=0; $i<count($result); $i++)
{
  $result[$i]['icon'] = sotf_Blob::cacheIcon2($result[$i]);
	$programmes["0:".$i] = $result[$i]["title"];
}

$smarty->assign("result", $result);
$smarty->assign("count", count($result));
$smarty->assign("programmes", $programmes);

$page->send();



?>