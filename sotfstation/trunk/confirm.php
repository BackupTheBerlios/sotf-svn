<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/

	/************************
	* Usersnew - add new users to the station admin panel
	*----------------------------------------
	* This page will allow the registration of new users to the station. This simply
	* inludes the creation of a new account.
	* 
	* 2DO - Tie to XMLRPC Interface
	************************/
	include("init.inc.php");												# include the global framwork
	include("classes/sendMail.class.php");					# include the mail sender
	$myNav->add($SECTION[CONFIRM],'users.php');			# add entry to Navigation Bar Stack
	
	$id = $_GET['action'];
	$smarty->assign("confirm_message",$CONFIRM[$id]);
	$smarty->assign("link",$_GET['next'] . ".php");
	
	//page output :)	
	pageFinish('confirm.htm');											# enter the desired template name as a parameter
?>