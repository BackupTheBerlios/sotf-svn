<?php

require("init.inc.php");
require("$classdir/sotf_AdvSearch.class.php");
require("$classdir/sotf_ParamCache.class.php");				//paramcache

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
	$pos = strstr($portal_http_new, "?");		//find post parameters
	if ($pos) $portal_http = substr($portal_http, 0, $pos);		//eliminate post parameters
	$smarty->assign("old_upload", $portal_http);	//save given URL (next time no nedd to write it again)
	$portal_http_new = str_replace("/portal.php/", "/portal_upload.php/", $portal_http);		//replace portal.php name with the php file responsible for upload
	if (strstr($portal_http_new, "/portal_upload.php/")) $file = @fopen ( $portal_http_new, "r");		//open file if string could be replaced
	if (!$file) $smarty->assign("upload_query", "http://");	//if not exist
	else	
		{
			$smarty->assign("upload_query", $portal_http_new);		//if exists
			$page->redirect($portal_http_new."?type=".$type."&data=".$data);
//			echo "OK";
			$_SESSION['portal_http'] = $portal_http;		//TODO save to user properties
		}
}
else $smarty->assign("old_upload", $_SESSION['portal_http']);		//TODO load from user properties



////SMARTY
//upload data
$smarty->assign("type", $type);
$smarty->assign("data", $data);
$smarty->assign("name", $name);

//$page->send();
$page->send("portal_upload.htm");
?>