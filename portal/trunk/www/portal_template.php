<?php

require("init.inc.php");
require("$classdir/sotf_Portal.class.php");

$t1 = sotf_Utils::getParameter('t1');
$t2 = sotf_Utils::getParameter('t2');
$t3 = sotf_Utils::getParameter('t3');

if (!isset($_SESSION["settings"])) $_SESSION["settings"] = $_SESSION["old_settings"];

$portal = new sotf_Portal("1");	//TODO:xxxxxx
$portal->setSettings($_SESSION["settings"]);

if (isset($t1))
{
	$sql="SELECT settings FROM portal_templates WHERE id='3'";
	$result = $db->getOne($sql);
	$settings = unserialize(base64_decode($result));
	$_SESSION["settings"] = $settings;	//save result
	$page->redirect($rootdir."/closeAndRefresh.php");		//close window and go back to edit mode
}
elseif (isset($t2))
{
	$sql="SELECT settings FROM portal_templates WHERE id='6'";
	$result = $db->getOne($sql);
	$settings = unserialize(base64_decode($result));
	$_SESSION["settings"] = $settings;	//save result
	$page->redirect("closeAndRefresh.php");		//close window and go back to edit mode
}
elseif (isset($t3))
{
	$sql="SELECT settings FROM portal_templates WHERE id='5'";
	$result = $db->getOne($sql);
	$settings = unserialize(base64_decode($result));
	$_SESSION["settings"] = $settings;	//save result
	$page->redirect("closeAndRefresh.php");		//close window and go back to edit mode
}

$smarty->assign("portal", $settings["portal"]);

$page->send("portal_template.htm");

?>