<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: Andr�s Micsik, M�t� Pataki, Tam�s D�ri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */


require("init.inc.php");

$contactId = sotf_Utils::getParameter('id');

$contact = & $repository->getObject($contactId);
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
