<?php

require("init.inc.php");

$smarty->assign('PAGETITLE',$page->getlocalized('edit_link'));

$page->forceLogin();

$linkId = sotf_Utils::getParameter('linkid');
$prgId = sotf_Utils::getParameter('prgid');
$save = sotf_Utils::getParameter('save');

if(empty($prgId))
     raiseError("Missing programme id!");

if (!hasPerm($prgId, "change")) {
  raiseError("You have no permission to change links here!");
}

if($linkId) {
  $link = & new sotf_NodeObject('sotf_links', $linkId);
  $smarty->assign('LINK_ID',$linkId);
} else {
  $link = & new sotf_NodeObject('sotf_links');
  $link->set('prog_id', $prgId);
}

// save general data
if($save) {
    $link->set('url', sotf_Utils::getParameter('url'));
    $link->set('caption', sotf_Utils::getParameter('caption'));
    if(sotf_Utils::getParameter('public_access'))
      $b = "true";
    else
      $b = "false";
    $link->set('public_access', $b);
    if($linkId)
      $link->update();
    else
      $link->create();
    $page->redirect("closeAndRefresh.php");
    exit;
}

// general data
if($linkId)
     $smarty->assign('LINK_DATA',$link->data);

$page->sendPopup();

?>
