<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id: admin.php 554 2006-04-12 10:37:20Z buddhafly $
 * Authors: András Micsik 
 */

require("init.inc.php");

$smarty->assign('PAGETITLE',$page->getlocalized('AdminPage'));

$page->forceLogin();

//$page->errorURL = "admin.php";

checkPerm('node', 'change');

$count = sotf_Programme::countAll();

$limit = $page->splitList($count, $scriptUrl);

$actionsToCount = "'listens','downloads'";

$sql = "SELECT p.id, p.title, s.id AS station_id, s.name AS station_name, count(distinct h.user_id) as count FROM sotf_programmes p, sotf_stations s, sotf_user_history h WHERE p.station_id=s.id AND p.id=h.object_id AND h.action IN ($actionsToCount) GROUP BY p.id, p.title, s.id, s.name ORDER BY count DESC";
$res =	$db->limitQuery($sql, $limit["from"] , $limit["maxresults"]);
if(DB::isError($res))
  raiseError($res);
$results = null;
while (DB_OK === $res->fetchInto($row)) {
  $row['groups'] = sotf_Group::listGroupsOfObject($row['id']);
  $results[] = $row;
}

debug("TOPLIST", $results);
$smarty->assign('TOPLIST', $results);

$page->send();

?>
