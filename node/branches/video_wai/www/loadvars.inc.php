<?php


// $ data assignements
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

// end $data assignements

// STATIONS assignement
$smarty->assign('STATIONS', sotf_Station::listStationNames());

// search languages assignements for searchbox
$searchLangs = $config['languages'];
array_unshift($searchLangs, "any_language");

for($i=0; $i<count($searchLangs); $i++) {
  $langNames[$i] = $page->getlocalized($searchLangs[$i]);
}

$smarty->assign('searchLangs', $searchLangs);
$smarty->assign('langNames', $langNames);
// end search languages assignements


// userspecific assignements
// YET TO BE TESTED
if ($page->loggedIn()) {

  // get users's playlist
  $playlist = new sotf_UserPlaylist();
  $smarty->assign('PLAYLIST', array_reverse($playlist->load()));  //changed by Klaus Temper, to show newest first
  $smarty->assign('PLAYLIST_COUNT', count($playlist->load()));    //added by Klaus Temper

}
// userspecific assignements

// TOPIC assignements
// get topics with most content
$smarty->assign('TOPICS', $vocabularies->getTopTopics(5));
// end TOPIC assignements

?>