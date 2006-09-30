<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id: admin.php 554 2006-04-12 10:37:20Z buddhafly $
 * Authors: András Micsik 
 */

require("init.inc.php");
$hitsPerPage = 50;

$smarty->assign('PAGETITLE',$page->getlocalized('AdminPage'));

$page->forceLogin();

//$page->errorURL = "admin.php";

checkPerm('node', 'change');

if(sotf_Utils::getParameter('del')) {
  $uid = sotf_Utils::getParameter('uid');
  $user = sotf_Group::getById($gid);
  $group->delete();
  $page->redirect('adminGroups.php');
  $page->logRequest();
  exit;
}

$pattern = sotf_Utils::getParameter('pattern');
$count = sotf_User::countUsers($pattern);
$limit = $page->splitList($count, $scriptUrl);
$users = sotf_User::listUsers($limit["from"] , $limit["maxresults"], $pattern);
$smarty->assign('USERS', $users);
$smarty->assign('PATTERN', $pattern);

$page->send();

?>
