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
	include('config.inc.php');									# guest what this is ;)
	session_start();
	session_destroy();
	header("Location: " . SRC_ROOT . "index.php");
	exit;
?>