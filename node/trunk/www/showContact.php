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
    $data = array();
    $data['role'] = $repository->getRoleName($role);
    if($class == 'sotf_station') {
      $data['url'] = "showStation.php?stationid=$id";
      $data['name'] = $obj->get('name');
      $data['mid'] = $page->getlocalized('of_station');
      //$locMsg = 'in_station';
    } elseif($class == 'sotf_series') {
      $data['url'] = "showSeries.php?seriesid=$id";
      $data['name'] = $obj->get('title');
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
