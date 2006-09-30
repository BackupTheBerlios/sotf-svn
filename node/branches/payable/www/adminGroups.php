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

if(sotf_Utils::getParameter('del')) {
  $gid = sotf_Utils::getParameter('gid');
  $group = sotf_Group::getById($gid);
  $group->delete();
  $page->redirect('adminGroups.php');
  $page->logRequest();
  exit;
}

$groups = sotf_Group::listAll();
$smarty->assign('GROUPS',$groups);

$page->send();

?>
