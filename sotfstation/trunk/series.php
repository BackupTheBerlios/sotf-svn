<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/

	/************************
	* Settings - lets the user manage his/her client appearance settings
	************************/
	include("init.inc.php");													# include the global framwork
	$myNav->add($SECTION[SERIES],'settings.php');			# add entry to Navigation Bar Stack
	
	
	//create help message
	//$myHelp = new helpBox(1,'98%');									# this will fetch a help message from the database and output it
																										# in the template (if allowed to do so)
																							
	//page output :)	
	pageFinish('series.htm');													# enter the desired template name as a parameter
	
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