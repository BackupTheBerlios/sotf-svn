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


// check if user has default query
if ($page->loggedIn()) {
  $advsearch = new sotf_AdvSearch();

  //$loadfrom ahol a default = 1
  $prefs = $user->getPreferences();
  $defQuery = $prefs->getDefaultQuery();
}

if($defQuery) {
  // show default query instead of new programmes
  $smarty->assign("DEF_QUERY", 1);
    debug("default query", $defQuery);

    $advsearch->Deserialize($defQuery);
    $query = $advsearch->GetSQLCommand();
  
    debug("query", $query);
    //get the number of results
    $max = $db->getOne("SELECT count(*) FROM (".$query.") as count");       
    $smarty->assign("DEF_QUERY_MAX", $max);

    $res = $db->limitQuery($query, 0, MAX_ITEMS_IN_INDEX);

    $hits = '';
    while (DB_OK === $res->fetchInto($row)) {
      $hits[] = $row;
      if(!empty($row['icon'])) {
        sotf_Programme::cacheIcon($row['id'], $db->unescape_bytea($row['icon']));
      }
    }
    $smarty->assign("NEWS", $hits);

} else {
  $smarty->assign('NEWS', sotf_Programme::getNewProgrammes($fromDay, MAX_ITEMS_IN_INDEX));
}




$smarty->assign('TOPICS', $repository->getTopTopics(5));

$page->send();

?>
