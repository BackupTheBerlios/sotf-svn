<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/

	/************************
	* No Access Page :: 08.11.2002
	*----------------------------------------
	* Display a No Access Page (403 Error)
	* 
	* Note: You may want to use this page as a starting point for the
	* creation of other subpages of the application.
	************************/
	include("init.inc.php");										# include the global framwork
	$myNav->add($SECTION[403],'index.php');			# add entry to Navigation Bar Stack

	//page output :)
	pageFinish('noaccess.htm');
?>