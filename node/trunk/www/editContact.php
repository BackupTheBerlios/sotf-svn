<?php

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
if (!hasPerm($contact->id, "change")) {
  raiseError("You have no permission to change contact settings!");
}

// upload icon
$uploadicon = sotf_Utils::getParameter('uploadicon');
if($uploadicon) {
  $file =  sotf_Utils::getFileInDir($user->getUserDir(),$_FILES['userfile']['name']);
  move_uploaded_file($_FILES['userfile']['tmp_name'], $file);
  $contact->setIcon($file);
  $page->redirect("editContact.php?id=$contactId#icon");
  exit;
}

// save general data
if($save || $finish1 || $finish2) {

  if(!$finish2) {
    $contact->set('alias', sotf_Utils::getParameter('alias'));
    $contact->set('acronym', sotf_Utils::getParameter('acronym'));
    $contact->set('intro', sotf_Utils::getParameter('intro'));
    $contact->set('email', sotf_Utils::getParameter('email'));
    $contact->set('address', sotf_Utils::getParameter('address'));
    $contact->set('phone', sotf_Utils::getParameter('phone'));
    $contact->set('cellphone', sotf_Utils::getParameter('cellphone'));
    $contact->set('fax', sotf_Utils::getParameter('fax'));
    $contact->set('url', sotf_Utils::getParameter('url'));
    $contact->update();
  }

  if($save) {
    $page->redirect("editContact.php?id=$contactId");
  } else {
    $page->redirect("closeAndRefresh.php?anchor=roles");
  }
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
$smarty->assign('CONTACT_DATA',$contact->data);

// user permissions: editors and managers
//$smarty->assign('PERMISSIONS', $permissions->listUsersAndPermissionsLocalized($st->id));


// icon and jingle
$smarty->assign('USERFILES',$user->getUserFiles());

if ($contact->getIcon()) {
  $smarty->assign('ICON','1');
}

$page->sendPopup();

?>
