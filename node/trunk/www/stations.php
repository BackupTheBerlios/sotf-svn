<?php
// -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

require("init.inc.php");

$station = sotf_Utils::getParameter('station');
$new = sotf_Utils::getParameter('new');
$delete = sotf_Utils::getParameter('delete');
$save = sotf_Utils::getParameter('save');
$desc = sotf_Utils::getParameter('desc');
$addstationmanager = sotf_Utils::getParameter('addstationmanager');
$delstationmanager = sotf_Utils::getParameter('delstationmanager');
$username = sotf_Utils::getParameter('username');

if ($station)
	$st = & new sotf_Station($station);

if (sotf_Node::hasPermission('create'))
{
	$smarty->assign('NODE_ADMIN',$station);
	if ($new)
	{
		$station_old = $station;
		$station = preg_replace("/[^a-zA-Z0-9_-]/","_",$station);

		$smarty->assign('STATION',$station);
		$smarty->assign('DESC',$desc);
		
		if ($station != $station_old)
		{
			$smarty->assign('STATUS',getlocalized(illegal_trackname));
		}
		else
		{
			sotf_Station::create($station, $desc);
			
			$page->redirect("editStation.php?station=$station");
		}
	}
	elseif ($delete)
	{
		$st = & new sotf_Station($station);
		$st->delete();
		$smarty->assign('STATUS',$page->getlocalized('delete_ok'));
	}
	elseif ($save)
	{
		$st = & new sotf_Station($station);
		$st->set('station', $station);
		$st->set('description', $desc);
		$st->save();
		
		$smarty->assign('STATUS',getlocalized('save_ok'));
	}
	elseif ($addstationmanager)
	{
		//$st = & new sotf_Station($station);
		//$st->addPermission(, $userid)
		if (sotf_Permission::addStationManager($username))
			$smarty->assign('STATUS',$page->getlocalized('addstationmanager_ok'));
		else
			$smarty->assign('STATUS',$page->getlocalized('addstationmanager_failed'));
	}
	elseif ($delstationmanager)
	{
		if (sotf_Permission::delStationManager($username))
			$smarty->assign('STATUS',$page->getlocalized('delstationmanager_ok'));
		else
			$smarty->assign('STATUS',$page->getlocalized('delstationmanager_failed'));
	}
	$users = sotf_Permission::getStationManagers();
	if (count($users)>0)
	{
		$smarty->assign('USERS',$users);
	}
}
$stations = sotf_Station::listAll();

for($i=0; $i<count($stations); $i++)
{
	/*
	$logo = false;
	if (isLocalStation($stations[$i]['station']))
	{
		if (!is_object($repository->getStationLogo($stations[$i]['station'])))
		{
			$logo = '<img border="0" src="getLogo.php?station='.rawurlencode($stations[$i]['station']).'">';
		}
	}
	else
	{
		$url = parse_url($repository->getRepositoryURL($stations[$i]['station']));
		$scheme = $url['scheme'];
		$host = $url['host'];
		$path = $url['path'];
		$logo = '<img border="0" src="'.$scheme.'://'.$host.$path.'/getLogo.php?station='.rawurlencode($stations[$i]['station']).'">';
		if($url['host'] == "node.streamonthefly.com") {
		  // This is because Thomas Hassan had not refreshed the CVS when I asked for it cca. 5 times.
		  debug("replaced AT2 logo");
		  $logo = '';
		}
	}
	*/
	
	if ($stations[$i]->getLogo())
		$logo = true;
	else
		$logo = true;
	$STATION_LIST[] = array(stationId		=> $stations[$i]->get('station'),
							desc			=> $stations[$i]->get('description'),
							numItems		=> $stations[$i]->numProgrammes(),
							logo			=> $logo,
							local			=> $stations[$i]->isLocal(),
							station_manager	=> sotf_Permission::get('station_manager',$stations[$i]->get('station')));

	if (sotf_Permission::get('station_manager',$stations[$i]->get('station')))
	{
		$LOCAL_STATION_MANAGER = true;
	}
}
if ($LOCAL_STATION_MANAGER)
{
	$smarty->assign('LOCAL_STATION_MANAGER',$LOCAL_STATION_MANAGER);
}

$smarty->assign('STATION_LIST',$STATION_LIST);

$page->send();

?>
