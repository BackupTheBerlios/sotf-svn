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

if (strpos($_SERVER['PHP_SELF'], "portal_login.php")) $page->redirect($rootdir."/portals.php");		//if called directly

$portal_name = substr($_SERVER["PATH_INFO"], 1);
//if (!isset($_SERVER["PATH_INFO"]) OR ($portal_name == "")) die("Error 404!");	//needs the information which portal

$portal = new sotf_Portal($portal_name);
$portal_id = $portal->getId();
if ($portal_id == NULL) $page->redirect($rootdir."/portals.php");

////user login and logout////
$username = sotf_Utils::getParameter('username');	//get username if sended
$password = sotf_Utils::getParameter('password');	//get password if sended

if (isset($username) AND isset($password))
{
	$user = new portal_user($portal_id, $username, $password);	//create user object with given data
	if ($user->loggedIn()) $page->redirect($_SERVER["PHP_SELF"]);	//redirect page
	else $page->redirect($_SERVER["PHP_SELF"]."?login=1");		//redirect to login page
}
else $user = new portal_user($portal_id);			//create user object with (in session) saved username

?>