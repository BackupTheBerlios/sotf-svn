<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/

	/************************
	* Logout Module :: 12.11.2002
	*----------------------------------------
	* Destroys all session data, hence logging the user out from the control panel
	************************/
	include('config.inc.php');											# guess what this is ;)
	
	//clean cookies
	setcookie("auto_login_id");
	setcookie("auto_login_key");
	setcookie("auto_login_name");
	
	//destroy session data			
	session_start();																# start the session handler
	session_destroy();															# destroy all session related data
	header("Location: " . SRC_ROOT . "index.php");	# redirect to root page
	exit;																						# exit
	
	/*
	* I'll be honest, I hate PHP... but only today, only now
	**/
?>