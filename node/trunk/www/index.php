<?php
require("init.inc.php");
require("$classdir/sotf_AdvSearch.class.php");

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
$data['numProgs'] = sotf_Programme::countAll();

$allStats = sotf_Programme::getAllStats();
$allStats['l_and_d'] = $allStats['listens'] + $allStats['downloads'];
$data['access'] = $allStats;

$fileStats = sotf_Programme::getFileStats();
$fileStats['size_mb'] = sprintf('%d', $fileStats['filesize'] / 1024 /1024);
$fileStats['length_hour'] = sprintf('%d', $fileStats['play_length'] / 60 / 60);
$data['files'] = $fileStats;

$data['numUsers'] = sotf_User::countUsers();

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
$dayInThePast = mktime(0,0,0, $now['mon'], $now['mday']-10, $now['year']);
$fromDay = date('Y-m-d', $dayInThePast);


if ($page->loggedIn()) {

  // get users's playlist
  $playlist = new sotf_UserPlaylist();
  $smarty->assign('PLAYLIST', $playlist->load());

  // check if user has default query
  $advsearch = new sotf_AdvSearch();
  $prefs = $user->getPreferences();
  $defQuery = $prefs->getDefaultQuery();
}

// show default query instead of new programmes
if($defQuery) {
  $smarty->assign("DEF_QUERY", 1);
    debug("default query", $defQuery);

    $advsearch->Deserialize($defQuery);
    $query = $advsearch->GetSQLCommand();
  
    debug("query", $query);
    //get the number of results
    $max = $db->getOne("SELECT count(*) FROM ( $query ) as foo ");       
    $smarty->assign("DEF_QUERY_MAX", $max);

    $res = $db->limitQuery($query, 0, MAX_ITEMS_IN_INDEX);

    $hits = '';
    while (DB_OK === $res->fetchInto($row)) {
      $row['icon'] = sotf_Blob::cacheIcon($row['id']);
      $hits[] = $row;
    }
    $smarty->assign("NEWS", $hits);

} else {
  // get new programmes
  $smarty->assign('NEWS', sotf_Programme::getNewProgrammes($fromDay, MAX_ITEMS_IN_INDEX));
}

// get topics with most content
$smarty->assign('TOPICS', $repository->getTopTopics(5));

$page->send();

?>
