<?php

require("portal_login.php");

$settings = $portal->loadSettings();	//load saved portal settings

$type = sotf_Utils::getParameter('type');
$data = sotf_Utils::getParameter('data');
$name = sotf_Utils::getParameter('name');
$portal_password = sotf_Utils::getParameter('portal_password');
$submit = sotf_Utils::getParameter('submit');

$d = array();
if ($type == "query") {$d['query'] = $data; $d['name'] = $name;}
else $d = $data;
$result = $portal->uploadData($type, $d, $portal_password);
//if ($result == "OK") $page->redirect($rootdir."/closeAndRefresh.php");		//close window
//else

if (isset($submit)) $smarty->assign("error", $result);						//error message in $result


////SMARTY
//directories and names
$smarty->assign("rootdir", $rootdir);				//root directory (portal/www)
$smarty->assign("php_self", $_SERVER['PHP_SELF']);		//php self for the form submit and hrefs
$smarty->assign("portal_name", $portal_name);			//name of the portal

$smarty->assign("portal", $settings["portal"]);

//upload data
$smarty->assign("type", $type);
$smarty->assign("data", $data);
$smarty->assign("name", $name);

$page->send("portal_upload.htm");
?>