<?php
define("DELIMITER", ':'); 

require("init.inc.php");

$page->forceLogin();

$station = sotf_Utils::getParameter('station');
$id = sotf_Utils::getParameter('id');
$okURL = sotf_Utils::getParameter('okURL');
$save = sotf_Utils::getParameter('save');
$delete = sotf_Utils::getParameter('delete');
$series_id = sotf_Utils::getParameter('series_id');
$title = sotf_Utils::getParameter('title');
$description = sotf_Utils::getParameter('description');
$editor = sotf_Utils::getParameter('editor');
$contact_email = sotf_Utils::getParameter('contact_email');

$errorstation = sotf_Utils::getParameter('errorstation');
$errorseriesid = sotf_Utils::getParameter('errorseriesid');
$errortitle = sotf_Utils::getParameter('errortitle');
$errordescription = sotf_Utils::getParameter('errordescription');
$erroreditor = sotf_Utils::getParameter('erroreditor');
$errorcontactemail = sotf_Utils::getParameter('errorcontactemail');

if ($station)
{
	$smarty->assign('STATION',$station);
}

if ($okURL)
{
	$smarty->assign('OKURL',$okURL);
}

if ($errorstation)
	$smarty->assign('ERRORSTATION',$errorstation);
if ($errorseriesid)
	$smarty->assign('ERRORSERIESID',$errorseriesid);
if ($errortitle)
	$smarty->assign('ERRORTITLE',$errortitle);
if ($errordescription)
	$smarty->assign('ERRORDESCRIPTION',$errordescription);
if ($erroreditor)
	$smarty->assign('ERROREDITOR',$erroreditor);
if ($errorcontactemail)
	$smarty->assign('ERRORCONTACTEMAIL',$errorcontactemail);

if ($id)
	$ser = & new sotf_Series($id);
else
	$ser = & new sotf_Series($station . DELIMITER . $series_id);
	
if ($station)
	$ser->set('station',$station);
if ($series_id)
	$ser->set('series_id',$series_id);
if ($title)
	$ser->set('title',$title);
if ($description)
	$ser->set('description',$description);
if ($editor)
	$ser->set('editor',$editor);
if ($contact_email)
	$ser->set('contact_email',$contact_email);

if (sotf_Permission::get("write",$station))
{
	$smarty->assign('EDIT_PERMISSION',true); 
	if ($save)
	{
		$error = "";
		if (!$station)
			$error .= "&errorstation=1";
		if (!$series_id)
			$error .= "&errorseriesid=1";
		if (!$title)
			$error .= "&errortitle=1";
		if (!$description)
			$error .= "&errordescription=1";
		if (!$editor)
			$error .= "&erroreditor=1";
		if (!$contact_email)
			$error .= "&errorcontactemail=1";
		if ($error)
			$page->redirect("editSeries.php?station=".rawurlencode($station).
							"&series_id=".rawurlencode($series_id).
							"&title=".rawurlencode($title).
							"&description=".rawurlencode($description).
							"&editor=".rawurlencode($editor).
							"&contact_email=".rawurlencode($contact_email).
							$error);
		$ser->save();
		if ($okURL)
		{
			$page->redirect($okURL);
		}
		else
		{
			$page->redirect("listProgrammes.php?station=".rawurlencode($station));
		}
	}
	elseif ($delete)
	{
		$ser->delete();
		$page->redirect("listProgrammes.php?station=".rawurlencode($station));
	}

	$editor = $ser->get('editor');
	if (!$editor)
		$editor = $user->name;
	$contact_email = $ser->get('contact_email');
	if (!$contact_email)
		$contact_email = $user->email;
	$series_item = array(
							series_id		=>	$ser->get('series_id'),
							title			=>	$ser->get('title'),
							description		=>	$ser->get('description'),
							editor			=>	$editor,
							contact_email	=>	$contact_email,
						);
	
	$smarty->assign('SERIES_ITEM',$series_item);
	$page->send();
}
else
{
	$page->halt(getlocalized('permission_error'));
}
?>
