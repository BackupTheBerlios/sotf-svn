<?php

require("init.inc.php");

$contactId = sotf_Utils::getParameter('id');

$contact = & new sotf_Contact($contactId);
$smarty->assign('PAGETITLE', $page->getlocalizedWithParams('programs_by', $contact->get('name')));
$smarty->assign('CONTACT_ID',$contactId);
$smarty->assign('CONTACT_NAME',$contact->get('name'));
$smarty->assign('CONTACT_DATA',$contact->getAllWithIcon());

$limit = $page->splitList($contact->countProgrammes(), myGetenv('REQUEST_URI'));
$progs = $contact->listProgrammes($limit["from"] , $limit["maxresults"]);

for($i=0; $i<count($progs); $i++) {
  $progs[$i]['icon'] = sotf_Blob::cacheIcon($progs[$i]['id']);
}

$smarty->assign('PROGS',$progs);

$page->send();

?>
