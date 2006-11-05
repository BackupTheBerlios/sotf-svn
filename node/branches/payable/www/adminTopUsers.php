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

$sql = "SELECT u.id, u.username, d.contact_person, count(distinct h.object_id) as count FROM sotf_users u LEFT JOIN sotf_user_data d ON d.user_id=u.id, sotf_user_history h WHERE u.id=h.user_id AND h.action IN ($actionsToCount) GROUP BY u.id, u.username, d.contact_person ORDER BY count DESC";
$res =	$db->limitQuery($sql, $limit["from"] , $limit["maxresults"]);
if(DB::isError($res))
  raiseError($res);
$results = null;
while (DB_OK === $res->fetchInto($row)) {
  $row['groups'] = sotf_Group::getGroupNames($row['id']);
  $results[] = $row;
}

$smarty->assign('TOPLIST', $results);

$page->send();

?>
