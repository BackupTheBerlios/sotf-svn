<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id: addToSeries.php 136 2003-03-05 09:11:40Z andras $
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: Andr�s Micsik, M�t� Pataki, Tam�s D�ri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

$smarty->assign("PAGETITLE", $page->getlocalized("addtoseries"));

$page->popup = true;
$page->forceLogin();

$prgId = sotf_Utils::getParameter('prgid');

$prg = & new sotf_Programme($prgId);

checkPerm($prgId, 'change');

$save = sotf_Utils::getParameter('save');
if($save) {
  $prg->setWithParam("series_id");
  $prg->update();
  $page->redirect("closeAndRefresh.php");
}

$smarty->assign('PRG_ID', $prgId);
$smarty->assign('PRG_TITLE', $prg->get('title'));
$smarty->assign('PRG_SERIES', $prg->get('series_id'));

$smarty->assign('MY_SERIES', $permissions->mySeriesData($prg->get('station_id')));

// generate output

$page->sendPopup();

?>