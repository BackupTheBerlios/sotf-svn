<?php

require("init.inc.php");

$contactId = sotf_Utils::getParameter('id');

$contact = & new sotf_Contact($contactId);
$smarty->assign('PAGETITLE', $contact->get('name'));
$smarty->assign('CONTACT_ID',$contactId);
$smarty->assign('CONTACT_NAME',$contact->get('name'));
$smarty->assign('CONTACT_DATA',$contact->data);

$page->sendPopup();

?>
