<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */


require("init.inc.php");

$smarty->assign('PAGETITLE',$page->getlocalized('edit_contact'));

$page->forceLogin();

$contactId = sotf_Utils::getParameter('id');
$save = sotf_Utils::getParameter('save');
$finish1 = sotf_Utils::getParameter('finish1');
$finish2 = sotf_Utils::getParameter('finish2');
$addperm = sotf_Utils::getParameter('addperm');
$delperm = sotf_Utils::getParameter('delperm');
$username = sotf_Utils::getParameter('username');

$view = sotf_Utils::getParameter('view');

$contact = & new sotf_Contact($contactId);
$smarty->assign('CONTACT_ID',$contactId);
$smarty->assign('CONTACT_NAME',$contact->get('name'));

if(!$contact->isLocal()) {
  raiseError("You can only edit local contacts!");
}

// delete
if(sotf_Utils::getParameter('delete')) {
  checkPerm($contact, "delete");
  $contact->delete();
  $page->redirect("closeAndRefresh.php?anchor=roles");
}

// manage permissions
$delperm = sotf_Utils::getParameter('delperm');
if($delperm) {
  checkPerm($contact, "authorize");
  $userid = sotf_Utils::getParameter('userid');
  if(empty($userid) || !is_numeric($userid)) {
    raiseError("Invalid userid: $userid");
  }
  $username = $user->getUsername($userid);
  if(empty($username)) {
    raiseError("Invalid userid: $userid");
  }
  $permissions->delPermission($contact->id, $userid);
  $msg = $page->getlocalizedWithParams("deleted_permissions_for", $username);
  $page->addStatusMsg($msg, false);
  $page->redirect("editContact.php?id=$contactId#perms");
  exit;
}

checkPerm($contact, "change", "authorize");

// upload icon
$uploadicon = sotf_Utils::getParameter('uploadicon');
if($uploadicon) {
  $file =  $user->getUserDir() . '/' . $_FILES['userfile']['name'];
  moveUploadedFile('userfile',  $file);
  $contact->setIcon($file);
  $page->redirect("editContact.php?id=$contactId#icon");
  exit;
}

// save general data
if($save || $finish1 || $finish2) {

  if(!$finish2) {
    $contact->setWithTextParam('alias');
    $contact->setWithTextParam('acronym');
    $contact->setWithTextParam('intro');
    $contact->setWithTextParam('email');
    $contact->setWithTextParam('address');
    $contact->setWithTextParam('phone');
    $contact->setWithTextParam('cellphone');
    $contact->setWithTextParam('fax');
	 $success = $contact->setWithUrlParam('url');
    $contact->update();
	 if($save || !$success)
		$page->redirect("editContact.php?id=$contactId");
  }
  $page->redirect("closeAndRefresh.php?anchor=roles");
  exit;
}

$seticon = sotf_Utils::getParameter('seticon');
if($seticon) {
  $filename = sotf_Utils::getParameter('filename');
  $file =  sotf_Utils::getFileInDir($user->getUserDir(), $filename);
  if ($contact->setIcon($file)) {
    //$page->addStatusMsg("icon_ok");
  } else {
    $page->addStatusMsg("icon_error");
  }
  $page->redirect("editContact.php?id=$contactId#icon");
}

// general data
$smarty->assign('CONTACT_DATA',$contact->getAllForHTML());

// user permissions: editors and managers
$smarty->assign('PERMISSIONS', $permissions->listUsersAndPermissionsLocalized($contact->id));


// icon and jingle
$smarty->assign('USERFILES',$user->getUserFiles());

$smarty->assign('ICON', $contact->cacheIcon());

$page->sendPopup();

?>
