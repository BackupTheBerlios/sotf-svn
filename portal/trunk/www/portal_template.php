<?php

require("portal_login.php");
//require("init.inc.php");
//require("$classdir/sotf_Portal.class.php");

$load = sotf_Utils::getParameter('load');

$settings = $_SESSION["settings"];			//load current settings from session
if ($settings["table"] == "")
{
	$settings = $portal->loadSettings();	//if not found load saved portal
	$_SESSION['old_settings'] = $settings;	//save as old settings
}
else $portal->setSettings($settings);				//if found init portal object with it

//if (!isset($_SESSION["settings"])) $_SESSION["settings"] = $_SESSION["old_settings"];
//$portal = new sotf_Portal("1");	//TODO:xxxxxx
//$portal->setSettings($_SESSION["settings"]);


if (isset($load))
{
	$sql="SELECT settings FROM portal_templates WHERE id='$load'";
	$result = $db->getOne($sql);
	$settings = unserialize(base64_decode($result));
	$_SESSION["settings"] = $settings;	//save result
	$page->redirect($rootdir."/closeAndRefresh.php");		//close window and go back to edit mode
}

$templates = $portal->getTemplates();		//get array

$smarty->assign("templates", $templates);			//list of all portals

//$smarty->assign("portal", $settings["portal"]);
$smarty->assign("home", $settings["home"]);
$page->send("portal_template.htm");

?>