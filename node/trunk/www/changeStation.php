<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

$smarty->assign("PAGETITLE", $page->getlocalized("changeStation"));

$page->popup = true;
$page->forceLogin();

$prgId = sotf_Utils::getParameter('prgid');

$prg = & new sotf_Programme($prgId);

checkPerm($prgId, 'change');

$save = sotf_Utils::getParameter('save');
if($save) {
  $newStationId = sotf_Utils::getParameter('station_id');
  if($prg->get('station_id') != $newStationId) {
	 $prg->set("station_id", $newStationId);
	 $prg->set("series_id", NULL);
	 $prg->update();
  }
  $page->redirect("closeAndRefresh.php");
}

$smarty->assign('PRG_ID', $prgId);
$smarty->assign('PRG_TITLE', $prg->get('title'));
$smarty->assign('PRG_STATION', $prg->get('station_id'));

$smarty->assign('MY_STATIONS', $permissions->listStationsForEditor(false));

// generate output

$page->sendPopup();

?>