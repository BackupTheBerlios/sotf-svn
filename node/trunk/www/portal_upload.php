<?php

require("init.inc.php");
require($config['classdir'] . "/sotf_AdvSearch.class.php");
require($config['classdir'] . "/sotf_ParamCache.class.php");				//paramcache

$type = sotf_Utils::getParameter('type');
$data = sotf_Utils::getParameter('data');
$portal_http = sotf_Utils::getParameter('portal_http');
//$name = sotf_Utils::getParameter('name');
//$portal_password = sotf_Utils::getParameter('portal_password');
$submit = sotf_Utils::getParameter('submit');

if ($portal_http == "") $portal_http = NULL;
if (isset($portal_http))
{
	if (substr($portal_http, 0, 7) != "http://") $portal_http = "http://".$portal_http;
	$pos = strpos($portal_http, "?");		//find post parameters
	if ($pos) $portal_http = substr($portal_http, 0, $pos);		//eliminate post parameters
	$smarty->assign("old_upload", $portal_http);	//save given URL (next time no nedd to write it again)
	$portal_http_new = str_replace("/portal.php/", "/portal_upload.php/", $portal_http);		//replace portal.php name with the php file responsible for upload
	if (strstr($portal_http_new, "/portal_upload.php/")) $file = @fopen ( $portal_http_new, "r");		//open file if string could be replaced
	if (!$file) $smarty->assign("error", $page->getlocalized("URL_not_found"));	//if not exist
	else			//if exists
	{
		$smarty->assign("upload_query", $portal_http_new);

		$_SESSION['portal_http'] = $portal_http;		//TODO save to user properties
		if ($user)	//if logged in
		{
			$prefs = $user->getPreferences();
			$prefs->portalSettings = array("URL" => $portal_http);
			$prefs->save();
		}

		$page->redirect($portal_http_new."?type=".$type."&data=".$data);

	}
}
elseif ($user)			//only if logged in
{
	$prefs = $user->getPreferences();
	$smarty->assign("old_upload", $prefs->portalSettings["URL"]);		//TODO load from user properties
}
else $smarty->assign("old_upload", $_SESSION['portal_http']);		//TODO load from session



////SMARTY
//upload data
$smarty->assign("type", $type);
$smarty->assign("data", $data);
$smarty->assign("name", $name);

//$page->send();
$page->send("portal_upload.htm");
?>