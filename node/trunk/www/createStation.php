<?php
// -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

require("init.inc.php");

$station = sotf_Utils::getParameter('station');
$new = sotf_Utils::getParameter('new');
$desc = sotf_Utils::getParameter('desc');
$manager = sotf_Utils::getParameter('username');

$page->forceLogin();

if (!hasPerm('node','create')) {
  raiseError($page->getlocalized('no_permission'));
  exit;
}

if ($new) {

  $userid = $user->getUserid($manager);
  if(empty($userid) || !is_numeric($userid)) {
    $page->addStatusMsg('select_manager');
    $problem = 1;
  }

  $station_old = $station;
  $station = sotf_Utils::makeValidName($station, 32);
  if ($station != $station_old) {
			$page->addStatusMsg('illegal_name');
      $problem = 1;
  }
  
	if(!$problem)	{
    $st = & new sotf_Station();
    $st->create($station, $desc);
    $permissions->addPermission($st->getID(), $userid, 'admin');
    $page->addStatusMsg('station_created');
    $page->redirect("stations.php");
  }
}

$smarty->assign('STATION',$station);
$smarty->assign('DESC',$desc);
$smarty->assign('MANAGER',$manager);
		
$page->send();

?>
