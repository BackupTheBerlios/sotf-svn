<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id: manageFiles.php 409 2005-09-08 10:22:11Z xir $
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */


require("init.inc.php");

$smarty->assign('PAGETITLE',$page->getlocalized('editUserGroups'));

$page->popup = true;

$page->forceLogin();

checkPerm('node', 'change');

// delete groups
$uid = sotf_Utils::getParameter('uid');
$smarty->assign('UID', $uid);

$del = sotf_Utils::getParameter('del');
if($del) {
  $uGroups = sotf_Group::listGroupsOfUser($uid);
  debug("U1", $uGroups);
  reset ($_POST);
  while(list($g,$val) = each($_POST)) {
    debug("P", "$g - $val");
    if(substr($g, 0, 2) == 'g_') {
      $g = substr($g, 2);
      sotf_Group::setGroup($uid, $g, 1);
      unset($uGroups[$g]);
    }
  }
  // remove unchecked items
  debug("U2", $uGroups);
  
  foreach($uGroups as $gid => $rid) {
    sotf_Group::setGroup($uid, $gid, 0, $rid);
  }
  $page->redirect("closeAndRefresh.php");
  exit;
}

// close
$close = sotf_Utils::getParameter('close');
if($close) {
  $page->redirect("closeAndRefresh.php");
  exit;
}

// generate output
$uGroups = sotf_Group::listGroupsOfUser($uid);
$groups = sotf_Group::listAll(0);
foreach($groups as $g) {
  $g['rid'] = $uGroups[$g['id']];
  $glist[] = $g;
}
$smarty->assign('GROUPS', $glist);

$page->sendPopup();

?>
