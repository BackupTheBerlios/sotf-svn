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

if(sotf_Utils::getParameter('del')) {
  $uid = sotf_Utils::getParameter('uid');
  $user = new sotf_User($uid);
  debug("Deleting user $uid", $user->username);
  $user->delete();
  $page->redirect('adminUsers.php');
  $page->logRequest();
  exit;
}

$pattern = sotf_Utils::getParameter('pattern');
$count = sotf_User::countUsers($pattern);
$limit = $page->splitList($count, $scriptUrl . "?pattern=".urlencode($pattern));
$users = sotf_User::listUsers($limit["from"] , $limit["maxresults"], $pattern);
foreach($users as $user) {
  $user['groups'] = join(', ',sotf_Group::getGroupNames($user['id']));
  $ulist[] = $user;
}
$smarty->assign('USERS', $ulist);
$smarty->assign('PATTERN', $pattern);

$page->send();

?>
