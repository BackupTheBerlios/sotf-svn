<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-

/*
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */


require("init.inc.php");
$hitsPerPage = $sotfVars->get("hitsPerPage", 15);

$smarty->assign('PAGETITLE',$page->getlocalized('Stations'));

$delete = sotf_Utils::getParameter('delete');
$changeMode = sotf_Utils::getParameter('change_mode');

if($changeMode) {
  $mode = sotf_Utils::getParameter('mode');
  $language = sotf_Utils::getParameter('language');
  if(!setcookie('sortMode', $mode))
         debug("could not set cookie for sort");
  if(!setcookie('filterLang', $language))
         debug("could not set cookie for filter");
} else {
  $mode = $_COOKIE['sortMode'];
  $language = $_COOKIE['filterLang'];
}

if ($delete) {
  $station = sotf_Utils::getParameter('station');
  if(!hasPerm('node','delete') && !hasPerm($station,'admin')) {
         $permTransl = $page->getlocalized('perm_delete');
         $msg = $page->getlocalizedWithParams('no_permission', $permTransl);
         raiseError($msg);
  }
  $st = & $repository->getObject($station);
  $st->delete();
  $page->addStatusMsg('delete_ok');
  $page->redirect($_SERVER["PHP_SELF"]);
}

$count = sotf_Station::countStations($language);

//$limit = $page->splitList($count, $config['localPrefix'] . "/stations.php");

$limit = $page->splitList($count, $scriptUrl);

$stations = sotf_Station::listStations($limit["from"] , $limit["maxresults"], $mode, $language);

for($i=0; $i<count($stations); $i++) {

  $sprops = $stations[$i]->getAllWithIcon();

  $sprops['numProgs'] = $stations[$i]->numProgrammes();
  $sprops['isLocal'] = $stations[$i]->isLocal();
  $sprops['languages'] = $stations[$i]->getLanguagesLocalized();
  if(hasPerm('node','delete', 'change')) {
    $sprops['managers'] = $permissions->listUsersWithPermission($stations[$i]->id, 'admin');
  }

  $STATION_LIST[] = $sprops;
}

$smarty->assign('STATIONS',$STATION_LIST);

$smarty->assign('MODE', $mode);
$smarty->assign('LANGUAGE', $language);

$smarty->assign('STATION_LANGS', sotf_Station::listStationLanguages());


// online counter for statistics
if ($config['counterMode']) {
   $chCounter_status = 'active';
   $chCounter_visible = 0;
   $chCounter_page_title = 'Station anzeigen - stations.php';
   include($config['counterURL']);
}

$page->send();

?>
