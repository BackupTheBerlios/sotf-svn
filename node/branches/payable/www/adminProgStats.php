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

$progId = sotf_Utils::getParameter('id');
$prog = &$repository->getObject($progId);
$smarty->assign("PRG", $prog->getAll());

$actionsToCount = "'listens','downloads'";

$sql = "SELECT distinct u.*, d.contact_person FROM sotf_users u LEFT JOIN sotf_user_data d ON d.user_id=u.id, sotf_user_history h WHERE u.id=h.user_id AND h.object_id='$progId' AND h.action IN ($actionsToCount) ORDER BY u.username";
$res = $db->query($sql);
if(DB::isError($res))
  raiseError($res);
$users = array();
while (DB_OK === $res->fetchInto($row)) {
  $row['groups'] = sotf_Group::getGroupNames($row['id']);
  $users[] = $row;
}

$smarty->assign("USERS", $users);

$page->sendPopup();

?>
