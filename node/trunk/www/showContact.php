<?php

require("init.inc.php");

$contactId = sotf_Utils::getParameter('id');

$contact = & new sotf_Contact($contactId);
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
    $class = get_class($obj);
    if($class == 'sotf_station') {
      $locMsg = 'in_station';
      $name = $obj->get('name');
    } elseif($class == 'sotf_series') {
      $locMsg = 'in_series';
      $name = $obj->get('title');
    }
    else {
      debug("unhandled class", $class);
      continue;
    }
    $ins[] = $page->getlocalizedWithParams($locMsg, $repository->getRoleName($role), $name);
  }
}
$smarty->assign('REFS',$ins);

$page->sendPopup();

?>
