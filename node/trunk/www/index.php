<?php
require("init.inc.php");

if($_REQUEST['select_station']) {
  $page->redirect("showStation.php?stationid=" . $_POST['station']);
}

/* defaults for main "index" page   */

define("MAX_ITEMS_IN_INDEX", 10);

$data['numNodes'] = sotf_Node::countAll();
if($data['numNodes']==0) {
     $data['numNodes']=1;
}
$data['numStations'] = sotf_Station::countAll();
$data['numProgs'] = sotf_Programme::countAll();
$smarty->assign($data);

$smarty->assign('STATIONS', sotf_Station::listStationNames());

$searchLangs = $languages;
array_unshift($searchLangs, "any_language");

for($i=0; $i<count($searchLangs); $i++) {
  $langNames[$i] = $page->getlocalized($searchLangs[$i]);
}

$smarty->assign('searchLangs', $searchLangs);
$smarty->assign('langNames', $langNames);

$now = getDate();
$yesterday = mktime(0,0,0, $now['mon'], $now['mday']-2, $now['year']);
$fromDay = date('Y-m-d', $yesterday);
$smarty->assign('NEWS', sotf_Programme::getNewProgrammes($fromDay, MAX_ITEMS_IN_INDEX));

$page->send();

?>
