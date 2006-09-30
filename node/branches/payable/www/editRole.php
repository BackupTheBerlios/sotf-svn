<?php  // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: Andr�s Micsik, M�t� Pataki, Tam�s D�ri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

$smarty->assign('PAGETITLE',$page->getlocalized('edit_role'));

$page->popup = true;
$page->forceLogin();

$roleId = sotf_Utils::getParameter('roleid');
//$contactId = sotf_Utils::getParameter('contactid');
$contactId = sotf_Utils::getParameter('contact');
$objectId = sotf_Utils::getParameter('objectid');
$save = sotf_Utils::getParameter('save');
$roleSelected = sotf_Utils::getParameter('role');
$scopeChange = sotf_Utils::getParameter('scope_change');
$scope = sotf_Utils::getParameter('scope');
$pattern = sotf_Utils::getParameter('pattern');

if(empty($objectId)) {
     raiseError("Object id is missing!");
}

checkPerm($objectId, "change");

if($scopeChange) {
  if(sotf_Utils::getParameter('change')) {
	 $scope = sotf_Utils::getParameter('newscope');
  }
  $newPattern = sotf_Utils::getParameter('newpattern');
  if($newPattern) {
	 $scope = 6;
  }
  $page->redirect("editRole.php?roleid=$roleId&objectid=$objectId&pattern=" . urlencode($newPattern) . "&scope=$scope");
}

if($roleId) {
  $role = & new sotf_NodeObject('sotf_object_roles', $roleId);
  $contact = new sotf_Contact($role->get('contact_id'));
  if($contactId)
    $smarty->assign("CONTACT_SELECTED", $contactId);
  else {
    $smarty->assign("CONTACT_NAME", $contact->get('name'));
    $smarty->assign("CONTACT_SELECTED", $contact->get('id'));
  }
  if($roleSelected)
    $smarty->assign("ROLE_SELECTED", $roleSelected);
  else
    $smarty->assign("ROLE_SELECTED", $role->get('role_id'));
} 

if($save) {
  if(!$roleSelected)
    raiseError("No role selected!");
  // save
  if(is_object($role)) {
    $role->set('contact_id', $contactId);
    $role->set('role_id', $roleSelected);
    $role->update();
  } else {
    if(sotf_ComplexNodeObject::findRole($objectId, $contactId, $roleSelected)) {
      // this role already exists
      $page->addStatusMsg("role_exists");
      $page->redirectSelf();
    }
    $role = new sotf_NodeObject("sotf_object_roles");
    $role->set('object_id', $objectId);
    $role->set('contact_id', $contactId);
    $role->set('role_id', $roleSelected);
    $role->create();
  }
  $page->redirect("closeAndRefresh.php?anchor=roles");
}

// general data
$obj = & $repository->getObject($objectId);

$smarty->assign("OBJECT_ID", $objectId);
$smarty->assign("STATION_ID", $obj->getStationId());
$smarty->assign("ROLE_ID", $roleId);
$smarty->assign('ROLE_LIST', $vocabularies->getRoles());

if(!$scope) {
	  $scope = 1;
}
switch($scope) {
 case 1: 
	$contacts = sotf_Contact::listMyContactNames();
	break;
 case 2:
	$contacts = array();
	break;
 case 3:
	$contacts = sotf_Contact::listObjectContactNames($obj);
	break;
 case 4:
	$contacts = sotf_Contact::listLocalContactNames();
	break;
 case 5:
	$contacts = sotf_Contact::listAllContactNames();
	break;
 case 6:
	$contacts = sotf_Contact::searchContactNames(sotf_Utils::getParameter('pattern'));
	break;
 default:
	raiseError("unknown scope: $scope");
}
			  

$smarty->assign('SCOPE', $scope);
$smarty->assign('PATTERN', $pattern);
$smarty->assign('CONTACTS', $contacts);

$page->sendPopup();

?>
