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
  checkPerm($contact->id, "delete");
  $contact->delete();
  $page->redirect("closeAndRefresh.php?anchor=roles");
}

checkPerm($contact->id, "change");

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
    $contact->set('alias', sotf_Utils::getParameter('alias'));
    $contact->set('acronym', sotf_Utils::getParameter('acronym'));
    $contact->set('intro', sotf_Utils::getParameter('intro'));
    $contact->set('email', sotf_Utils::getParameter('email'));
    $contact->set('address', sotf_Utils::getParameter('address'));
    $contact->set('phone', sotf_Utils::getParameter('phone'));
    $contact->set('cellphone', sotf_Utils::getParameter('cellphone'));
    $contact->set('fax', sotf_Utils::getParameter('fax'));
	 $url = sotf_Utils::getParameter('url');
	 if($url != 'http://') {
		if(sotf_Utils::is_valid_URL($url)) {
		  $contact->set('url', $url);
		} else {
		  $error = 1;
		  $page->addStatusMsg("invalid-url");
		}
	 }
    $contact->update();
  }

  if($save || $error) {
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
