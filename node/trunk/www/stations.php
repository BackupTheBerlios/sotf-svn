<?php
// -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

require("init.inc.php");
$hitsPerPage = $sotfVars->get("hitsPerPage", 15);

$smarty->assign('PAGETITLE',$page->getlocalized('Stations'));

$start = sotf_Utils::getParameter('start');
$station = sotf_Utils::getParameter('station');
$delete = sotf_Utils::getParameter('delete');

if ($delete and hasPerm('node','delete'))
{
  $st = & new sotf_Station($station);
  $st->delete();
  $page->addStatusMsg('delete_ok');
  $page->redirect($_SERVER["PHP_SELF"]);
}

$limit = $page->splitList(sotf_Station::countAll(), "$php_self");

//$result = $db->limitQuery($query, $limit["from"], $limit["maxresults"]);				//get results with limit

$stations = sotf_Station::listStations($limit["from"] , $limit["maxresults"]);

for($i=0; $i<count($stations); $i++) {
	
  $sprops = $stations[$i]->getAllWithIcon();
  
  $sprops['numProgs'] = $stations[$i]->numProgrammes();
  $sprops['isLocal'] = $stations[$i]->isLocal();
  if(hasPerm('node','delete')) {
    $sprops['managers'] = $permissions->listUsersWithPermission($stations[$i]->id, 'admin');
  }

  $STATION_LIST[] = $sprops;
}

$smarty->assign('STATIONS',$STATION_LIST);

$page->send();

?>
