<?php
// -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

require("init.inc.php");

$smarty->assign('PAGETITLE',$page->getlocalized('edit_right'));

$page->forceLogin();

$rightId = sotf_Utils::getParameter('rid');
$objectId = sotf_Utils::getParameter('objectid');
$save = sotf_Utils::getParameter('save');

if (!hasPerm($objectId, "change")) {
  raiseError("You have no permission to change these settings!");
}

if(empty($objectId)) {
     raiseError("Object id is missing!");
}

if($rightId) {
  $rights = & new sotf_NodeObject('sotf_rights', $rightId);
  $smarty->assign("START_TIME", $rights->get('start_time'));
  $smarty->assign("STOP_TIME", $rights->get('stop_time'));
  $smarty->assign("RIGHTS_TEXT", $rights->get('rights_text'));
} else {
  $newRight = 1;
  $smarty->assign('NEW', 1);
}

if($save) {
  $startTime = sotf_Utils::getParameter('start_time');
  $stopTime = sotf_Utils::getParameter('stop_time');
  $rightsText = sotf_Utils::getParameter('rights_text');
  $fullProg = sotf_Utils::getParameter('fullprog');
  // TODO check input params
  // save
  if($newRight)
    $rights = new sotf_NodeObject("sotf_rights");
  if(!$fullProg) {
    $rights->set('start_time', $startTime);
    $rights->set('stop_time', $stopTime);
  }
  $rights->set('rights_text', $rightsText);
  if($newRight) {
    $rights->set('prog_id', $objectId);
    $rights->create();
  } else
    $rights->update();
  $page->redirect("closeAndRefresh.php");
}

// general data
$smarty->assign("OBJECT_ID", $objectId);

$page->sendPopup();

?>
