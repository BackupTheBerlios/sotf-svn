<?php
// -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

require("init.inc.php");

$addstationmanager = sotf_Utils::getParameter('addstationmanager');
$delstationmanager = sotf_Utils::getParameter('delstationmanager');
$username = sotf_Utils::getParameter('username');

if ($station)
	$st = & new sotf_Station($station);

if ($permissions->hasNodePermission('create'))
{
	$smarty->assign('NODE_ADMIN', '1');
	if ($new)
	{
    $station = sotf_Utils::getParameter('station');
		$station_old = $station;
		$station = preg_replace("/[^a-zA-Z0-9_-]/","_",$station);

		$smarty->assign('STATION',$station);
		$smarty->assign('DESC',$desc);
		
		if ($station != $station_old)
		{
			$page->addStatusMsg('illegal_trackname');
		}
		else
		{
      $st = & new sotf_Station();
			$st->create($station, $desc);
      $page->addStatusMsg('station_created');
			$page->redirect("editStation.php?stationid=" . $st->getID());
		}
	}
	elseif ($delete)
	{
		$st = & new sotf_Station($station);
		$st->delete();
		$page->addStatusMsg('delete_ok');
    $page->redirect($_SERVER["PHP_SELF"]);
	}
	elseif ($save)
	{
		$st = & new sotf_Station($station);
		$st->create($station, $desc);
		$page->addStatusMsg('save_ok');
    $page->redirect($_SERVER["PHP_SELF"]);
	}
	elseif ($addstationmanager)
	{
    $userid = $user->getUserid($username);
		$permissions->addNodePermission('create', $userid);
		$page->addStatusMsg('addstationmanager_ok');
    $page->redirect($_SERVER["PHP_SELF"]);
	}
	elseif ($delstationmanager)
	{
    $userid = sotf_Utils::getParameter('userid');
		$permissions->delNodePermission('create', $userid);
		$page->addStatusMsg('delstationmanager_ok');
    $page->redirect($_SERVER["PHP_SELF"]);
	}

	$users = $permissions->listNodeUsersWithPerm('create');
	if (count($users)>0)
    {
      $smarty->assign('USERS',$users);
    }
}


// TODO: page splitting in station list!

$stations = sotf_Station::listStations($start, $hitsPerPage);

for($i=0; $i<count($stations); $i++)
{
	
	if ($stations[$i]->getLogo())
    $hasLogo = true;

  // get access rights for station
  $stationMgr = $permissions->hasPermission($stations[$i]->getId(), 'change');
	if ($stationMgr)
		$LOCAL_STATION_MANAGER = true;

	$STATION_LIST[] = array('id'		=> $stations[$i]->get('id'),
                          'name'	=> $stations[$i]->get('name'),
                          'description'	=> $stations[$i]->get('description'),
                          'numProgs'		=> $stations[$i]->numProgrammes(),
                          'hasLogo'			=> $hasLogo,
                          'isLocal'			=> $stations[$i]->isLocal(),
                          'stationManager'	=> $stationMgr);

}
if ($LOCAL_STATION_MANAGER)
{
	$smarty->assign('LOCAL_STATION_MANAGER',$LOCAL_STATION_MANAGER);
}



$smarty->assign('STATIONS',$STATION_LIST);

$page->send();

?>
