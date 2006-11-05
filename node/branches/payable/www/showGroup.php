<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id: showContact.php 372 2005-02-03 15:15:51Z micsik $
 * Author: Andras Micsik
 */

require("init.inc.php");

$gid = sotf_Utils::getParameter('id');
$group = new sotf_Group($gid);
if(!$group)
  raiseError("no_such_object", "Group $gid");

$smarty->assign('PAGETITLE', $group->get('name'));
$smarty->assign('GID',$gid);
$smarty->assign('GROUP_DATA',$group->getAll());

$objects = $group->listObjectsOfGroup();
rsort($objects);

$numProgs = 0;
while(list(,$oid)=each($objects)) {
    $obj = &$repository->getObject($oid);
	 if(!$obj) {
		logError("DB integrity error: role $role for $id which does not exist");
		continue;
	 }
    $class = get_class($obj);
    $data = array();
	 $data['name'] = $obj->get('name');
    if($class == 'sotf_station') {
      $data['url'] = $config['rootUrl'] . "/showStation.php/$oid";
      $data['mid'] = $page->getlocalized('Station');
      $data['count'] = $obj->numProgrammes();
    } elseif($class == 'sotf_series') {
      $data['url'] = $config['rootUrl'] . "/showSeries.php/$oid";
      $data['mid'] = $page->getlocalized('series');
		$data['count'] = $obj->numProgrammes();
    } elseif($class == 'sotf_programme') {
      $data['name'] = $obj->get('title');
      $data['url'] = $config['rootUrl'] . "/get.php/$oid";
      $data['mid'] = $page->getlocalized('Program');
    } else {
      logError("unhandled class", $class);
      continue;
    }
    $ins[] = $data;
}
$smarty->assign('OBJECTS',$ins);

$page->sendPopup();

?>
