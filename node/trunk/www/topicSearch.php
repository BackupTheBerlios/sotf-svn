<?php
require("init.inc.php");

$ID = sotf_Utils::getParameter('ID');
//$NAME = sotf_Utils::getParameter('NAME');

if($ID) {
     $NAME = $repository->getTopicName($ID);
}

// TODO: garbage collect :-)

$query="SELECT * FROM (".
	" SELECT sotf_programmes.*, sotf_stations.name as station, sotf_series.title as serietitle, sotf_series.description as seriedescription FROM sotf_programmes".
	" LEFT JOIN sotf_stations ON sotf_programmes.station_id = sotf_stations.id".
	" LEFT JOIN sotf_series ON sotf_programmes.series_id = sotf_series.id".
	") as programmes WHERE published = 't' AND".
	" (programmes.id = sotf_prog_topics.prog_id".
	" and sotf_prog_topics.topic_id = '$ID')";

$max = $db->getOne("SELECT count(*) FROM (".$query.") as count");	//get the number of results

$limit = $page->splitList($max, "$php_self?ID=$ID");
//$result = $db->limitQuery($query, $limit["from"], $limit["maxresults"]);				//get results with limit
$result = $db->getAll($query.$limit["limit"]);

// cache icons for results
for($i=0; $i<count($result); $i++) {
  $result[$i]['icon'] = sotf_Blob::cacheIcon($result[$i]['id']);
}


$smarty->assign("ID", $ID);						//topic id
$smarty->assign("NAME", $NAME);						//topic name
$smarty->assign("query", $query);					//query
$smarty->assign("result", $result);					//result array

$page->send("main_frame_left.htm");
?>