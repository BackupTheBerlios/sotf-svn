<?php  // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

// get number of seconds from time in seconds or in min:seconds format
function getTime($text) {
  if(is_numeric($text))
	 return $text;
  if(preg_match("/(\d+):(\d+)/", trim($text), $mm)) {
	 $time = $mm[1]*60+$mm[2];
	 return $time;
  }
  return NULL;
}

$smarty->assign('PAGETITLE',$page->getlocalized('edit_right'));

$page->popup = true;
$page->forceLogin();

$rightId = sotf_Utils::getParameter('rid');
$objectId = sotf_Utils::getParameter('objectid');
$save = sotf_Utils::getParameter('save');

checkPerm($objectId, "change");

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
  $startTime = getTime(sotf_Utils::getParameter('start_time'));
  $stopTime = getTime(sotf_Utils::getParameter('stop_time'));
  $rightsText = sotf_Utils::getParameter('rights_text');
  $fullProg = sotf_Utils::getParameter('fullprog');
  // save
  if($newRight)
    $rights = new sotf_NodeObject("sotf_rights");
  if($fullProg) {
    $rights->set('start_time', null);
    $rights->set('stop_time', null);
  } else {
    // check input params
    if(!is_numeric($startTime) || !is_numeric($stopTime))
      raiseError("not_integer");
    $rights->set('start_time', $startTime);
    $rights->set('stop_time', $stopTime);
  }
  $rights->set('rights_text', $rightsText);
  if($newRight) {
    $rights->set('prog_id', $objectId);
    $rights->create();
  } else {
    $rights->update();
  }
  $page->redirect("closeAndRefresh.php?anchor=rights");
}

// general data
$smarty->assign("OBJECT_ID", $objectId);

$page->sendPopup();

?>
