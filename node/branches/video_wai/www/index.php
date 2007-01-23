<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id: index.php 564 2006-05-09 12:03:56Z buddhafly $
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

// --- moved $data assignements to loadvars.inc.php

// --- moved STATIONS assignement to loadvars.inc.php

// --- moved search languages assignements to loadvars.inc.php

$now = getDate();
//$dayInThePast = mktime(0,0,0, $now['mon'], $now['mday']-10, $now['year']);
$dayInThePast = time() - (60*60*24*30); // 30 days back
$fromDay = date('Y-m-d', $dayInThePast);


// --- moved userspecific assignements to loadvars.inc.php
if ($page->loggedIn()) {

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

// --- moved topics assignements to loadvars.inc.php

$db->commit();

$page->send();

?>
