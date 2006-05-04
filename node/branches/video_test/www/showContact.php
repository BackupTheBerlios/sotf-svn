<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */


require("init.inc.php");

$contactId = sotf_Utils::getParameter('id');

if(strpos($contactId, '@') !== FALSE) {
  // someone tries with e-mail address
  $contact = new sotf_Contact;
  $contact->set('email', $contactId);
  $contact->find();
  if($contact->exists()) {
	 $foundByEmail = 1;
	 $contactId = $contact->id;
  }
}

if(!$foundByEmail)
  $contact = & $repository->getObject($contactId);

if(!$contact)
  raiseError("no_such_object", $contactId);

$smarty->assign('PAGETITLE', $contact->get('name'));
$smarty->assign('CONTACT_ID',$contactId);
$smarty->assign('CONTACT_NAME',$contact->get('name'));
$smarty->assign('CONTACT_DATA',$contact->getAllWithIcon());

$numProgs = $contact->countProgrammes();
$progs = $page->getlocalizedWithParams('has_programs', $numProgs);
$smarty->assign('PROGS',$progs);
$smarty->assign('NUMPROGS',$numProgs);

$refs = $contact->references();
while(list(,$row)=each($refs)) {
  $id = $row['object_id'];
  $role = $row['role_id'];
  if($repository->getTable($id) != 'sotf_programmes') {
    $obj = $repository->getObject($id);
	 if(!$obj) {
		logError("DB integrity error: role $role for $id which does not exist");
		continue;
	 }
    $class = get_class($obj);
    $data = array();
    $data['role'] = $vocabularies->getRoleName($role);
	 $data['name'] = $obj->get('name');
    if($class == 'sotf_station') {
      $data['url'] = $config['rootUrl'] . "/showStation.php/$id";
      $data['mid'] = $page->getlocalized('of_station');
      //$locMsg = 'in_station';
    } elseif($class == 'sotf_series') {
      $data['url'] = $config['rootUrl'] . "/showSeries.php/$id";
      $data['mid'] = $page->getlocalized('of_series');
      //$locMsg = 'in_series';
    }
    else {
      debug("unhandled class", $class);
      continue;
    }
    //$data['text'] = $page->getlocalizedWithParams($locMsg, $data['role'], $data['name']);
    $ins[] = $data;
  }
}
$smarty->assign('REFS',$ins);

$page->sendPopup();

?>
