<?php  // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id: editNeighbour.php 339 2003-12-03 08:39:25Z andras $
 * Author: András Micsik
 */

require("init.inc.php");

$smarty->assign('PAGETITLE',$page->getlocalized('AdminPage'));

$page->forceLogin();
$page->popup = true;
$page->errorURL = "editGroup.php";

checkPerm('node', "change");

$gid = sotf_Utils::getParameter('gid');
if($gid)
  $group = sotf_Group::getById($gid);
else
  $group = new sotf_Group();
#	  raiseError("No such group: $gid");

// save changes
if(sotf_Utils::getParameter('save')) {
  $oldName = $group->get('name');
  $group->setWithTextParam('name', 'name');
  $group->setWithTextParam('comments', 'comments');
  $group->setWithParam('price', 'price');
  $name = $group->get('name');
  if(!$name)
    $error = "error_name_missing";
  else {
    $ex = sotf_Group::getByName($name);
    if($ex and (!$gid or ($gid and $name != $oldName)))
      $error = 'error_name_in_use';
  }
  if(!$error) {
    if($gid) {
      // updating
      $group->update();
    } else {
      // creating      
      $group->create();  
    }
    $page->redirect("closeAndRefresh.php");
    exit;
  } else {
    $smarty->assign("ERROR", $page->getlocalized($error));
  }
}

// generate output

$smarty->assign('GROUP', $group->getAll());

$page->sendPopup();

?>