<?php
/*
includes all neccesary classes
checks login and portal name

sets variables:
 $portal	portal object
 $user		user object
 $portal_name	name of the portal
 $portal_id	id of the portal
 $username	username
 $password	user password
*/

require("init.inc.php");
require("$classdir/sotf_Portal.class.php");

if (strpos($_SERVER['PHP_SELF'], "portal_login.php")) $page->redirect($rootdir."/index.php");		//if called directly

$portal_name = substr($_SERVER["PATH_INFO"], 1);
//if (!isset($_SERVER["PATH_INFO"]) OR ($portal_name == "")) die("Error 404!");	//needs the information which portal

$portal = new sotf_Portal($portal_name);
$portal_id = $portal->getId();

//count page_impression
if ($portal_id == NULL) $query = "UPDATE portal_statistics SET number=number+1, timestamp='".$db->getTimestampTz()."' WHERE name='page_impression' AND portal_id IS NULL";
else $query = "UPDATE portal_statistics SET number=number+1, timestamp='".$db->getTimestampTz()."' WHERE name='page_impression' AND portal_id = $portal_id";
$db->query($query);


if ( $portal_id == NULL AND !(strpos($_SERVER['PHP_SELF'], "index.php")) ) $page->redirect($rootdir."/index.php");	//redirect if no such portal AND not called from there

////user login and logout////
if (sotf_Utils::getParameter('login_user'))	//if login button pressed
{
	$username = sotf_Utils::getParameter('username');	//get username if sended
	$password = sotf_Utils::getParameter('password');	//get password if sended
	$a_number = sotf_Utils::getParameter('a_number');	//get activisation number
}

if (isset($portal_id))
{
	if ( isset($username) AND isset($password) )
	{
		$user = new portal_user($portal_id, $username, $password);	//create user object with given data

		if (isset($a_number))
			if (!$user->activateUser($portal_id, $username, $password, $a_number)) $page->redirect($_SERVER["PHP_SELF"]."?login=1&activate=1&uname=$username");
		else	//create new user object
			$user = new portal_user($portal_id, $username, $password);	//create user object with given data

		if ($user->loggedIn()) $page->redirect($_SERVER["PHP_SELF"]); //redirect page
		if ($user->getActivated()) $page->redirect($_SERVER["PHP_SELF"]."?login=1&activate=1&uname=$username");	//redirect page
		else $page->redirect($_SERVER["PHP_SELF"]."?login=2&uname=$username");		//redirect to login page if bad login
	}
	else $user = new portal_user($portal_id);			//create user object with (in session) saved username
}
else $user = new portal_user("-1", "-1", "-1");			//create user object without login to a portal

?>