<?php  // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id: createSeries.php 206 2003-05-30 08:23:41Z andras $
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

$page->popup = true;
$page->forceLogin();

$stationId = sotf_Utils::getParameter('stationid');
$seriesName = sotf_Utils::getParameter('name');

checkPerm($stationId, "create");

if($seriesName) {
  // create a new series
  $series = new sotf_Series();
  $series->set('name', $seriesName);
  $series->set('station_id', $stationId);
  $series->set('entry_date', date('Y-m-d'));
  $status = $series->create();
  if(!$status) {
    $page->addStatusMsg('series_create_failed');
  } else {
    $permissions->addPermission($series->id, $user->id, 'admin');
    $page->redirect("editSeries.php?seriesid=" . $series->id);
    exit;
  }
}

// general data
$smarty->assign("NAME", $seriesName);

$page->sendPopup();

?>
