<?php
// -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

require("init.inc.php");

$page->popup = true;
$page->forceLogin();

//$contactId = sotf_Utils::getParameter('contactid');
$contactName = sotf_Utils::getParameter('name');

if($contactName) {

  if(sotf_Contact::findByNameLocal($contactName)) {
    //$page->addStatusMsg('contact_name_exists');
    //$page->redirect("createCOntact.php);
    raiseError("contact_name_exists");
    exit;
  }

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
