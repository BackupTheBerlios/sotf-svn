<?php

require("init.inc.php");
require("$classdir/sotf_Portal.class.php");

//Create new portal
$name = sotf_Utils::getParameter('name');
$portal_password = sotf_Utils::getParameter('portal_password');
$username = sotf_Utils::getParameter('username');
$user_password = sotf_Utils::getParameter('user_password');
$email = sotf_Utils::getParameter('email');

//Create new template (copy from an existing portal))
$portal_id = sotf_Utils::getParameter('portal_id');
$save_template = sotf_Utils::getParameter('save_template');

//var_dump($name);
//var_dump($portal_password);
//var_dump($username);
//var_dump($user_password);
//var_dump($email);

var_dump($portal_id);
var_dump($save_template);

if (sotf_Utils::getParameter('create_portal'))		//if create portal button pressed
if ($name != "" AND $portal_password != "" AND $username != "" AND $user_password != "" AND $email != "")
{
	$sql= "SELECT id FROM portal_settings WHERE name='$name'";
	//print($sql);
	$portal_id = $db->getOne($sql);
	if ($portal_id != NULL)
	{
		$smarty->assign("error", "Already exists");
	}
	else
	{
		$sql = "INSERT INTO portal_settings (name, template_id, password) VALUES ('$name', 1, '$portal_password')";
		$result = $db->query($sql);
		$sql= "SELECT id FROM portal_settings WHERE name='$name' AND password='$portal_password'";
		$portal_id = $db->getOne($sql);
		
		$sql="INSERT INTO portal_users (portal_id, name, password, email) VALUES ('$portal_id', '$username', '$user_password', '$email')";
		$result = $db->query($sql);
		$sql = "SELECT id FROM portal_users WHERE portal_id=$portal_id AND name='$username' AND password='$user_password'";
		$user_id = $db->getOne($sql);

		$sql = "UPDATE portal_settings SET admin_id = $user_id WHERE id = $portal_id";
		$result = $db->query($sql);

		$smarty->assign("error", "Portal created!");
	}
}
else $smarty->assign("error", "All fields are required!");

$portal = new sotf_Portal("");			//get list af all portals, create portal object
$portals = $portal->getPortals();		//get array
$portal_list = array();				//create new array
foreach($portals as $p) $portal_list[$p['id']] = $p['name'];	//extract information from array

$smarty->assign("php_self", $_SERVER['PHP_SELF']);		//php self for the form submit and hrefs
$smarty->assign("name", $name);
$smarty->assign("username", $username);
$smarty->assign("email", $email);
$smarty->assign("portals", $portal_list);			//list of all portals

$page->send("admin.htm");

?>