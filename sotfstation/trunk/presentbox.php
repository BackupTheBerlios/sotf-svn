<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/

	/************************
	* Present Box Page
	*----------------------------------------
	* Purpose of page goes here
	************************/
	include("init.inc.php");										# include the global framwork
	$myNav->add("Present Box",'index.php');			# add entry to Navigation Bar Stack
	authorize('edit_presentbox');								# check access rights
	
	//create help message
	//$myHelp = new helpBox(1);									# this will fetch a help message from the database and output it
																							# in the template (if allowed to do so)
																							
	//page output :)
	pageFinish('presentbox.htm');								# enter the desired template name as a parameter
	//pageFinishPopup('noaccess.htm');					# same as above but in a popop
?>
