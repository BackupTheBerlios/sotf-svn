<?php  // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

$page->popup = true;
$page->forceLogin();

$stationId = sotf_Utils::getParameter('stationid');
$seriesTitle = sotf_Utils::getParameter('title');

checkPerm($stationId, "create");

if($seriesTitle) {
  // create a new series
  $series = new sotf_Series();
  $series->set('title', $seriesTitle);
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
$smarty->assign("TITLE", $seriesTitle);

$page->sendPopup();

?>
