<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/

	/************************
	* Index Page - Login Framework
	*----------------------------------------
	* The purpose of this file is to process user authorization
	* either using the existing connection to SADM oresle make
	* an XMLRCP call.
	************************/
	include("init.inc.php");			# include the global framwork
	$myNav->add('Login','index.php');
	
	
	pageFinish('login.htm');
?>