<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/

	/************************
	* Users - the overview page of all active users on the station
	*----------------------------------------
	* This page will display a list of all the possible users registered with
	* this station and their access levels. The Administrator can always
	* add new users to the list or take away privileges or even delete them...
	************************/
	include("init.inc.php");											# include the global framwork
	$myNav->add($SECTION[USERS],'users.php');			# add entry to Navigation Bar Stack
	
	
	//create help message
	//$myHelp = new helpBox(1,'98%');							# this will fetch a help message from the database and output it
																								# in the template (if allowed to do so)
																							
	//page output :)	
	pageFinish('users.htm');											# enter the desired template name as a parameter
	
	/*
	* Sometimes I hate PHP, Really
	* 															- the coder -
	**/
	
	/*
	* There are times, when your eyes start falling out and you wish for the screen to dissolve
	* in liquid plasma. @Long live da komputa@ geeks say, is this piece of metal my god, or my
	* fiercest enemy... I shall not stand...
	* 															- the coder - 
	**/
?>