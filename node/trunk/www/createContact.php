<?php
// -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

require("init.inc.php");

$page->forceLogin();

//$contactId = sotf_Utils::getParameter('contactid');
$contactName = sotf_Utils::getParameter('name');

if($contactName) {
  // create a new contact
  $contact = new sotf_Contact();
  $status = $contact->create($contactName);
  if(!$status) {
    $page->addStatusMsg('contact_create_failed');
  } else {
    $permissions->addPermission($contact->id, $user->id, 'admin');
    $page->redirect("editContact.php?id=" . $contact->id);
    exit;
  }
}

// general data
$smarty->assign("NAME", $contactName);

$page->sendPopup();

?>
