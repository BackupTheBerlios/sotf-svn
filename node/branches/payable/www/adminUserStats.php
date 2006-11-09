<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id: admin.php 554 2006-04-12 10:37:20Z buddhafly $
 * Author: András Micsik 
 */

require("init.inc.php");

$smarty->assign('PAGETITLE',$page->getlocalized('AdminPage'));

$page->forceLogin();

//$page->errorURL = "admin.php";

checkPerm('node', 'change');

$userId = sotf_Utils::getParameter('id');
$user = new sotf_User($userId);
if(!$user)
  raiseError("no_such_object", $userId);

$smarty->assign("USER", $user);
$smarty->assign("UDATA", sotf_UserData::getSmartyData($userId));

$actionsToCount = "'listens','downloads'";

$sql = "SELECT DISTINCT p.id, p.title, s.id AS station_id, s.name AS station_name FROM sotf_programmes p, sotf_stations s, sotf_user_history h WHERE p.station_id=s.id AND p.id=h.object_id AND h.user_id='$userId' AND h.action IN ($actionsToCount) ORDER BY p.title";
$res = $db->query($sql);
if(DB::isError($res))
  raiseError($res);
$progs = array();
while (DB_OK === $res->fetchInto($row)) {
  $row['groups'] = sotf_Group::listGroupsOfObject($row['id']);
  $progs[] = $row;
}

$smarty->assign("PROGS", $progs);

$page->sendPopup();

?>
