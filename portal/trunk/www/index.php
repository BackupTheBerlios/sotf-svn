<?php

require("portal_login.php");
$portals = $portal -> getPortals();
$smarty->assign("portals", $portals);

//foreach ($portals as $p) print("<a href=\"portal.php/".$p['name']."\">".$p['name']."</a><br>");

if (sotf_Utils::getParameter('create_new_portal'))
{
	$name = sotf_Utils::getParameter('name');
	$portal_password = sotf_Utils::getParameter('portal_password');
	$username = sotf_Utils::getParameter('username');
	$user_password = sotf_Utils::getParameter('user_password');
	$email = sotf_Utils::getParameter('email');
	
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
			
			//$sql="INSERT INTO portal_users (portal_id, name, password, email) VALUES ('$portal_id', '$username', '$user_password', '$email')";
			//$result = $db->query($sql);
			//$sql = "SELECT id FROM portal_users WHERE portal_id=$portal_id AND name='$username' AND password='$user_password'";
			//$user_id = $db->getOne($sql);
			$user_id = $user->addNewUser($portal_id, $username, $user_password, $email);
	
			$sql = "UPDATE portal_settings SET admin_id = $user_id WHERE id = $portal_id";
			$db->query($sql);

			$sql = "INSERT INTO portal_statistics(name, number, portal_id) VALUES('page_impression', 0, $portal_id);";
			$db->query($sql);

			//$smarty->assign("error", "Portal created!");
			$page->redirect($rootdir."/portal.php/$name");
		}
	}
	else $smarty->assign("error", "All fields are required!");
	
	
	$smarty->assign("php_self", $_SERVER['PHP_SELF']);		//php self for the form submit and hrefs
	$smarty->assign("name", $name);
	$smarty->assign("username", $username);
	$smarty->assign("email", $email);
}

$page->send("index.htm");
?>
