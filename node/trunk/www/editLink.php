<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */


require("init.inc.php");

$smarty->assign('PAGETITLE',$page->getlocalized('edit_link'));

$page->forceLogin();

$linkId = sotf_Utils::getParameter('linkid');
$prgId = sotf_Utils::getParameter('prgid');
$save = sotf_Utils::getParameter('save');

if(empty($prgId))
     raiseError("Missing programme id!");

checkPerm($prgId, "change");

if($linkId) {
  $link = & new sotf_NodeObject('sotf_links', $linkId);
  $smarty->assign('LINK_ID',$linkId);
} else {
  $link = & new sotf_NodeObject('sotf_links');
  $link->set('prog_id', $prgId);
}

// save general data
if($save) {
  $url = sotf_Utils::getParameter('url');
  $link->set('url', $url);
  $link->set('caption', sotf_Utils::getParameter('caption'));
  if(sotf_Utils::getParameter('public_access'))
	 $b = "true";
  else
	 $b = "false";
  $link->set('public_access', $b);
  if(sotf_Utils::is_valid_URL($url)) {
    if($linkId)
      $link->update();
    else
      $link->create();
    $page->redirect("closeAndRefresh.php?anchor=links");
    exit;
  } else {
	 $page->addStatusMsg("invalid-url");
  }
}

// general data
if($linkId)
     $smarty->assign('LINK_DATA',$link->data);

$page->sendPopup();

?>
