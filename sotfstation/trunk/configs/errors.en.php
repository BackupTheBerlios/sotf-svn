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
		'5'		=> 'An XMLRPC Error has occured',
		'6'		=> 'Cannot delete user, the specified user is still running series on the station, please move to other users!',
		'7'		=> 'Cannot delete user, The specified user is the last admin of the station, and hence cannot be deleted!',
		'8'		=> 'Please enter a valid unix-like login name!',
		'9'		=> 'Please enter a valid e-mail address for the new user!',
		'10'	=> 'Please specify a valid password that is made of alphanumeric charachters only!',
		'11'	=> 'Please enter some user name for identification purposes!',
		'12'	=> 'This login is already taken, please choose another login name',
		'13'	=> 'The specified group does not exist, please change the Station Management configuration settings!',
		'14'	=> 'Cannot edit user access status, The specified user is the last admin of the station, and hence cannot be edited!',
		'15'	=> 'Please enter a title for the series!',
		'16'	=> 'Please enter a description for the series!',
		'17'	=> 'Programme end time must be greater than programme start time!'
	);
	
	//section names, used for building the navigation bar
	$SECTION = array(
		'LOGIN'		=> 'Login',
		'HOME'		=> 'Home',
		'403'			=> 'No Access',
		'INSIDE'	=> 'Programme',
		'ADDPROG' => 'New Programme',
		'USERS'		=> 'Users',
		'USERSNEW'=> 'Add New User',
		'SETTINGS'=> 'Settings',
		'SERIES'	=> 'Series',
		'CONFIRM'	=> 'Confirmation'
	);
	
	$ACTION = array(
		0			=> 'Logged In'
	);
	
	$STRING = array(
		'NEWUSER'	=> 'Access Password To Station'
	);
	
	$CONFIRM = array(
		1			=> 'A new user has been created, you will be shortly redirected to the user management page!',
		2			=> 'A new programme has been added to the station management console, you will be shortly redirected to the programme overview page!'
	);
?>