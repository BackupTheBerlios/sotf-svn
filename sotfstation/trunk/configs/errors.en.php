<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/

	/************************
	* Possible Errors Listing :: 08.11.2002
	*----------------------------------------
	* This file acts as a language interface when reporting errors
	************************/
	
	//all the possible errors that may crawl up in the code
	$ERR = array(
		'1' 	=> 'Incorrect Combination User / Password',
		'2'		=> 'Unable to connect to specified RPC Server',
		'3'		=> 'Unable to connect to local Station Database',
		'4'		=> 'Unable to connect to SADM Database',
		'5'		=> 'An XMLRPC Error has occured'
	);
	
	//section names, used for building the navigation bar
	$SECTION = array(
		'LOGIN'		=> 'Login',
		'HOME'		=> 'Home',
		'403'			=> 'No Access'
	)
?>