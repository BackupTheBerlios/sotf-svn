<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id: createContact.php 225 2003-06-12 16:46:59Z andras $
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

$page->popup = true;
$page->forceLogin();

$stationId = sotf_Utils::getParameter('stationid');
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
  $status = $contact->create($contactName, $stationId);
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
$smarty->assign("STATION_ID", $stationId);

$page->sendPopup();

?>
