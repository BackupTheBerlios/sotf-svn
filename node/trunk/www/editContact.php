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
$setlogo = sotf_Utils::getParameter('setlogo');
$filename = sotf_Utils::getParameter('filename');

$path_parts = pathinfo(realpath($filename));
$filename = $path_parts['basename'];

$contact = & new sotf_Contact($contactId);
$smarty->assign('CONTACT_ID',$contactId);
$smarty->assign('CONTACT_NAME',$contact->get('name'));

if(!$contact->isLocal()) {
  raiseError("You can only edit local contacts!");
}
if (!hasPerm($contact->id, "change")) {
  raiseError("You have no permission to change contact settings!");
}

// upload to my files
$upload = sotf_Utils::getParameter('upload');
if($upload) {
  move_uploaded_file($_FILES['userfile']['tmp_name'], $user->getUserDir() . '/' . $_FILES['userfile']['name']);
  $page->redirect("editContact.php?id=$contactId#logo");
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
    $page->redirect("closeAndRefresh.php");
  }
  exit;
}

if($setlogo) {
  $file = & new sotf_File($user->getUserDir().'/'.$filename);
  if ($contact->setLogo($file)) {
    //$page->addStatusMsg("icon_ok");
  } else {
    $page->addStatusMsg("icon_error");
  }
  $page->redirect("editContact.php?id=$contactId#logo");
}

// general data
$smarty->assign('CONTACT_DATA',$contact->data);

// user permissions: editors and managers
//$smarty->assign('PERMISSIONS', $permissions->listUsersAndPermissionsLocalized($st->id));


// logo and jingle
$smarty->assign('USERFILES',$user->getUserFiles());

if ($contact->getLogo()) {
  $smarty->assign('LOGO','1');
}

$page->sendPopup();

?>
