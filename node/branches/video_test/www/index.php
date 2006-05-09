<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");
require($config['classdir'] . "/sotf_AdvSearch.class.php");

if($_REQUEST['select_station']) {
  $page->redirect($config['localPrefix'] . "/showStation.php/" . $_POST['station']);
}

$db->begin();

$data['numNodes'] = sotf_Node::countAll();
if($data['numNodes']==0) {
     $data['numNodes']=1;
}
$data['numStations'] = sotf_Station::countAll();

$data['numAudioProgs'] = sotf_Programme::countAll('sound'); //MOD BY Martin Schmidt
$data['numVideoProgs'] = sotf_Programme::countAll('video'); //MOD BY Martin Schmidt


$allStats = sotf_Statistics::networkStats();
$allStats['l_and_d'] = $allStats['listens'] + $allStats['downloads'];
$data['access'] = $allStats;

$audioFileStats = sotf_Programme::getFileStats('sound'); //MOD BY Martin Schmidt
$audioFileStats['size_mb'] = sprintf('%d', $audioFileStats['filesize'] / 1024 /1024); //MOD BY Martin Schmidt
$audioFileStats['length_hour'] = sprintf('%d', $audioFileStats['play_length'] / 60 / 60); //MOD BY Martin Schmidt


//ADDED BY Martin Schmidt
$videoFileStats = sotf_Programme::getFileStats('video'); 
$videoFileStats['size_mb'] = sprintf('%d', $videoFileStats['filesize'] / 1024 /1024);
$videoFileStats['length_hour'] = sprintf('%d', $videoFileStats['play_length'] / 60 / 60);
/////////////////////////

$data['audioFiles'] = $audioFileStats;
$data['videoFiles'] = $videoFileStats;
$data['allFiles']['size_mb'] = $videoFileStats['size_mb']+$audioFileStats['size_mb'];
$data['allFiles']['length_hour'] = $videoFileStats['length_hour']+$audioFileStats['length_hour'];
$data['numAllProgs']=$data['numAudioProgs']+$data['numVideoProgs'];

$data['numUsers'] = sotf_User::countUsers();

$smarty->assign($data);

$smarty->assign('STATIONS', sotf_Station::listStationNames());

$searchLangs = $config['languages'];
array_unshift($searchLangs, "any_language");

for($i=0; $i<count($searchLangs); $i++) {
  $langNames[$i] = $page->getlocalized($searchLangs[$i]);
}

$smarty->assign('searchLangs', $searchLangs);
$smarty->assign('langNames', $langNames);

$now = getDate();
//$dayInThePast = mktime(0,0,0, $now['mon'], $now['mday']-10, $now['year']);
$dayInThePast = time() - (60*60*24*30); // 30 days back
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

// show default query or new programmes
$maxItemsIndexPage = $sotfVars->get("maxItemsIndexPage", 10);

if($defQuery) {
  $smarty->assign("DEF_QUERY", 1);
    debug("default query", $defQuery);

    $advsearch->Deserialize($defQuery);
    $query = $advsearch->GetSQLCommand();
  
    debug("query", $query);
    //get the number of results
    $max = $db->getOne("SELECT count(*) FROM ( $query ) as foo ");       
    $smarty->assign("DEF_QUERY_MAX", $max);

    $res = $db->limitQuery($query, 0, $maxItemsIndexPage);

    $hits = '';
    while (DB_OK === $res->fetchInto($row)) {
      $row['icon'] = sotf_Blob::cacheIcon2($row);
      $hits[] = $row;
    }
    $smarty->assign("NEWS", $hits);

} else {
  // get new programmes
  $smarty->assign('NEWS', sotf_Programme::getNewProgrammes($fromDay, $maxItemsIndexPage));
}

// get topics with most content
$smarty->assign('TOPICS', $vocabularies->getTopTopics(5));

$db->commit();

$page->send();

?>
