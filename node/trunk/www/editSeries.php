<?php

require("init.inc.php");

$seriesid = sotf_Utils::getParameter('seriesid');
$page->errorURL = "editSeries.php?seriesid=$seriesid";
$page->setTitle('edit_series');
$page->popup = true;
$page->forceLogin();

if(!$seriesid) {
  raiseError("Id is missing");
}

if (!hasPerm($seriesid, "change")) {
  raiseError("You have no permission to change series settings!");
}

$series = & new sotf_Series($seriesid);

// save general data
$save = sotf_Utils::getParameter('save');
$finish = sotf_Utils::getParameter('finish');
if($save || $finish) {
  $series->setWithParam('title');
  $series->setWithParam('description');
  $series->update();
  if($finish)
    $page->redirect("closeAndRefresh.php?anchor=series");
  else
    $page->redirect("editSeries.php?seriesid=$seriesid");
  exit;
}

// manage roles
$delrole = sotf_Utils::getParameter('delrole');
if($delrole) {
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
