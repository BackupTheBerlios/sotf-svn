<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

$station = sotf_Utils::getParameter('station');
$new = sotf_Utils::getParameter('new');
$desc = sotf_Utils::getParameter('desc');
$manager = sotf_Utils::getParameter('username');

$page->forceLogin();

checkPerm('node','create');

if ($new) {

  if($manager) {
	 $userid = $user->getUserid($manager);
	 if(empty($userid) || !is_numeric($userid)) {
		$page->addStatusMsg('invalid_user');
		$problem = 1;
	 }
  } else {
	 $userid = $user->id;
  }

  $station_old = $station;
  $station = sotf_Utils::makeValidName($station, 32);
  if ($station != $station_old) {
			$page->addStatusMsg('illegal_name');
      $problem = 1;
  }

  if(sotf_Station::isNameInUse($station)) {
    $page->addStatusMsg('name_in_use');
    $problem = 1;
  }
  
	if(!$problem)	{
    $st = & new sotf_Station();
    $st->create($station, $desc);
	 if($manager || !hasPerm('node', 'change'))
		$permissions->addPermission($st->getID(), $userid, 'admin');
    $page->addStatusMsg('station_created');
	 if(hasPerm('node', 'change') || !$manager)
		$page->redirect("editStation.php?stationid=" . $st->id);
	 else
		$page->redirect("stations.php");
  }
}

$smarty->assign('STATION',$station);
$smarty->assign('DESC',$desc);
$smarty->assign('MANAGER',$manager);
		
$page->send();

?>
