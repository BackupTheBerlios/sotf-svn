<?php  // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */


require("init.inc.php");

$seriesid = sotf_Utils::getParameter('seriesid');
$page->errorURL = "editSeries.php?seriesid=$seriesid";
$page->setTitle('edit_series');
$page->popup = true;
$page->forceLogin();

if(!$seriesid) {
  raiseError("Id is missing");
}

$series = & new sotf_Series($seriesid);

checkPerm($series->id, "change", 'authorize');

// save general data
$save = sotf_Utils::getParameter('save');
$finish = sotf_Utils::getParameter('finish');
$finish2 = sotf_Utils::getParameter('finish2');
if($save || $finish) {
  checkPerm($series->id, "change");
  $series->setWithParam('title');
  $series->setWithParam('description');
  $series->update();
}
if($finish || $finish2) {
  $page->redirect("closeAndRefresh.php?anchor=series");
}
if($save) {
  $page->redirect("editSeries.php?seriesid=$seriesid");
}

// manage roles
$delrole = sotf_Utils::getParameter('delrole');
if($delrole) {
  checkPerm($series->id, "change");
  $roleid = sotf_Utils::getParameter('roleid');
  $role = new sotf_NodeObject('sotf_object_roles', $roleid);
  $c = new sotf_Contact($role->get('contact_id'));
  $role->delete();
  //$msg = $page->getlocalizedWithParams("deleted_contact", $c->get('name'));
  //$page->addStatusMsg($msg, false);
  $page->redirect("editSeries.php?seriesid=$seriesid#roles");
  exit;
}

// manage permissions
$delperm = sotf_Utils::getParameter('delperm');
if($delperm) {
  checkPerm($series->id, "authorize");
  $username = sotf_Utils::getParameter('username');
  $userid = $user->getUserid($username);
  if(empty($userid) || !is_numeric($userid)) {
    raiseError("Invalid username: $username");
  }
  $permissions->delPermission($series->id, $userid);
  $msg = $page->getlocalizedWithParams("deleted_permissions_for", $username);
  $page->addStatusMsg($msg, false);
  $page->redirect("editSeries.php?seriesid=$seriesid#perms");
  exit;
}

// icon and jingle

// upload icon
$uploadIcon = sotf_Utils::getParameter('uploadicon');
if($uploadIcon) {
  checkPerm($series->id, "change");
  $file =  $user->getUserDir() . '/' . $_FILES['userfile']['name'];
  moveUploadedFile('userfile',  $file);
  if ($series->setIcon($file)) {
    //$page->addStatusMsg("ok_icon");
  } else {
    $page->addStatusMsg("error_icon");
  }
  $page->redirect("editSeries.php?seriesid=$seriesid#icon");
  exit;
}

// icon from my files
$seticon = sotf_Utils::getParameter('seticon');
if($seticon) {
  checkPerm($series->id, "change");
  $filename = sotf_Utils::getParameter('filename');
  $file =  sotf_Utils::getFileInDir($user->getUserDir(), $filename);
  if ($series->setIcon($file)) {
    //$page->addStatusMsg("ok_icon");
  } else {
    $page->addStatusMsg("error_icon");
  }
  $page->redirect("editSeries.php?seriesid=$seriesid#icon");
}

// generate output

// general data
$smarty->assign('SERIES_ID',$seriesid);
$smarty->assign('SERIES',$series->get('title'));

$smarty->assign('SERIES_DATA',$series->getAll());
$smarty->assign('SERIES_MANAGER',true);
$smarty->assign('ROLES', $series->getRoles());

// user permissions: editors and managers
$smarty->assign('PERMISSIONS', $permissions->listUsersAndPermissionsLocalized($series->id));

// icon and jingle
$smarty->assign('USERFILES',$user->getUserFiles());

if ($series->getIcon()) {
  $smarty->assign('ICON','1');
}

$page->sendPopup();

?>
