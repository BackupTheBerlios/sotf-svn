<?php

require("init.inc.php");

$page->forceLogin();
$station = sotf_Utils::getParameter('station');
$group = sotf_Utils::getParameter('group');
$username = sotf_Utils::getParameter('username');
$addusergroup = sotf_Utils::getParameter('addusergroup');
$delusergroup = sotf_Utils::getParameter('delusergroup');
$deluserstation = sotf_Utils::getParameter('deluserstation');
$view = sotf_Utils::getParameter('view');
$setjingle = sotf_Utils::getParameter('setjingle');
$setlogo = sotf_Utils::getParameter('setlogo');
$filename = sotf_Utils::getParameter('filename');

// status parameters
$oklogo = sotf_Utils::getParameter('oklogo');
$errorlogo = sotf_Utils::getParameter('errorlogo');
$okjingle = sotf_Utils::getParameter('okjingle');
$errorjingle = sotf_Utils::getParameter('errorjingle');
$okaddusergroup = sotf_Utils::getParameter('okaddusergroup');
$erroraddusergroup = sotf_Utils::getParameter('erroraddusergroup');
$okdelusergroup = sotf_Utils::getParameter('okdelusergroup');
$errordelusergroup = sotf_Utils::getParameter('errordelusergroup');
$okdeluserstation = sotf_Utils::getParameter('okdeluserstation');
$errordeluserstation = sotf_Utils::getParameter('errordeluserstation');

$path_parts = pathinfo(realpath($filename));
$filename = $path_parts['basename'];

$st = & new sotf_Station($station);

if ($station)
	$smarty->assign('STATION',$station);
if ($group)
	$smarty->assign('GROUP',$group);

// set messages to user
if ($oklogo)
	$smarty->assign('OKLOGO',$oklogo);
if ($errorlogo)
	$smarty->assign('ERRORLOGO',$errorlogo);
if ($okjingle)
	$smarty->assign('OKJINGLE',$okjingle);
if ($errorjingle)
	$smarty->assign('ERRORJINGLE',$errorjingle);
if ($okaddusergroup)
	$smarty->assign('OKADDUSERGROUP',$okaddusergroup);
if ($erroraddusergroup)
	$smarty->assign('ERRORADDUSERGROUP',$erroraddusergroup);
if ($okdelusergroup)
	$smarty->assign('OKDELUSERGROUP',$okdelusergroup);
if ($errordelusergroup)
	$smarty->assign('ERRORDELUSERGROUP',$errordelusergroup);
if ($okdeluserstation)
	$smarty->assign('OKDELUSERSTATION',$okdeluserstation);
if ($errordeluserstation)
	$smarty->assign('ERRORDELUSERSTATION',$errordeluserstation);

if ((sotf_Permission::get("station_manager",$station) || sotf_Permission::get("station_manager")) && $st->isLocal($station))
{
	if ($addusergroup)
	{
		if (sotf_Permission::addUserToGroup($username,$group,$station))
			$status = "&okaddusergroup=1";
		else
			$status = "&erroraddusergroup=1";
		$page->redirect("editStation.php?station=".rawurlencode($station).$status."#admin_users");
	}
	elseif ($delusergroup)
	{
		if (sotf_Permission::delUserFromGroup($username,$group,$station))
			$status = "&okdelusergroup=1";
		else
			$status = "&errordelusergroup=1";
		$page->redirect("editStation.php?station=".rawurlencode($station).$status."#admin_users");
	}
	elseif ($deluserstation)
	{
		if (sotf_Permission::delUserFromStation($username,$station))
			$status = "&okdeluserstation=1";
		else
			$status = "&errordeluserstation=1";
		$page->redirect("editStation.php?station=".rawurlencode($station).$status."#admin_users");
	}
	elseif($view)
	{
		$page->redirect("getUserFile.php/".$filename."?filename=".rawurlencode($filename));
	}
	elseif($setjingle)
	{
		$audiofile = & new sotf_AudioFile($user->getUserDir() . '/' . $filename);
		if ($st->setJingle($audiofile))
			$status = "&okjingle=1";
		else
			$status = "&errorjingle=1";
		$page->redirect("editStation.php?station=".rawurlencode($station).$status."#manage_files");
	}
	elseif($setlogo)
	{
		$file = & new sotf_File($user->getUserDir().'/'.$filename);
		if ($st->setLogo($file))
			$status = "&oklogo=1";
		else
			$status = "&errorlogo=1";
		$page->redirect("editStation.php?station=".rawurlencode($station).$status."#manage_files");
	}
	$usergroups = sotf_Permission::getUsersAndGroups($station);

	for ($i=0;$i<count($usergroups);$i++)
	{
			$USERS[$usergroups[$i]['username']]['username'] = $usergroups[$i]['username'];
			$USERS[$usergroups[$i]['username']]['groups'][] = $usergroups[$i]['group_id'];
	}
	if ($USERS)
		$smarty->assign('USERS',$USERS);

	$GROUPS = sotf_Permission::getGroups();
	if ($GROUPS)
	{
		$smarty->assign('GROUPS',$GROUPS);
	}

	$smarty->assign('USERFILES',$user->getUserFiles());
	if ($st->getLogo())
	{
		$smarty->assign('LOGO','getStationLogo.php/icon.png?station='.rawurlencode($station));
	}

	$jinglelist = & new sotf_FileList();
	$jinglelist->getAudioFromDir($st->getStationDir());
	$dellist = array();		// stores files to remove from $jinglelist
	for ($i=0;$i<count($jinglelist->list);$i++)
		if (substr($jinglelist->list[$i]->name,0,6) != "jingle")
			$dellist[] = $jinglelist->list[$i]->getPath();
	for ($i=0;$i<count($dellist);$i++)
		$jinglelist->remove($dellist[$i]);

	// now $jinglelist contains the jingles
	$checker = & new sotf_AudioCheck($jinglelist);		// check $jinglelist

	$JINGLE = array();
	for ($i=0;$i<count($audioFormats);$i++)
	{
		if ($checker->reqs[$i][0])
			$resmgs = '<a href="getJingle.php/' . $jinglelist->list[$checker->reqs[$i][1]]->name . '?station='.rawurlencode($station).'&index=' . $i . '">' . $jinglelist->list[$checker->reqs[$i][1]]->name . '</a>';
		else
			$resmgs = '<font color="red">' . $page->getlocalized("missing") . '</font>';
		$JINGLE[] = array($resmgs,$audioFormats[$i]['format'],$audioFormats[$i]['bitrate'],$audioFormats[$i]['channels'],$audioFormats[$i]['samplerate']);
	}
	$smarty->assign('JINGLE',$JINGLE);

	//$retval = $st->getJingle();
	$smarty->assign('OKURL',$PHP_SELF . '?station=' . rawurlencode($station));
	$smarty->assign('STATION_DATA',$st->data);
	$smarty->assign('STATION_MANAGER',true);
	$page->send();
}
else
{
	$page->halt($page->getlocalized('permission_error'));
}

?>
