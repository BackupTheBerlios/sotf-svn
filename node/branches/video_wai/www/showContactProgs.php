<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id: showContactProgs.php 358 2004-04-29 12:46:01Z micsik $
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */


require("init.inc.php");

$contactId = sotf_Utils::getParameter('id');

$contact = & new sotf_Contact($contactId);
$smarty->assign('PAGETITLE', $page->getlocalizedWithParams('programs_by', $contact->get('name')));
$smarty->assign('CONTACT_ID',$contactId);
$smarty->assign('CONTACT_NAME',$contact->get('name'));
$smarty->assign('CONTACT_DATA',$contact->getAllWithIcon());

$limit = $page->splitList($contact->countProgrammes(), "$scriptUrl/$contactId");
$progs = $contact->listProgrammes($limit["from"] , $limit["maxresults"]);

for($i=0; $i<count($progs); $i++) {
  $progs[$i]['icon'] = sotf_Blob::cacheIcon2($progs[$i]);
}

$smarty->assign('PROGS',$progs);

$page->send();

?>
