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
		'17'	=> 'Programme end time must be greater than programme start time!',
		'18'	=> 'Series end date must be greater than the series start date!',
		'19'	=> 'Cannot create a one day series, please create a singe programme instead!',
		'20'	=> 'Please select at least one day for the show to run in!',
		'21'	=> 'Please enter a valid set of keywords',
		'22'	=> 'Please enter a valid programme description!',
		'23'	=> 'Please ebter all the relevant rights information to programme!',
		'24'	=> 'Please enter an existing not future creation date!',
		'25'	=> 'Please enter a valid not future issuing date!',
		'26'	=> 'You do not have enough access rights to publish this programme!',
		'27'	=> 'Please make sure you have specified all the neccesary meta data for this programme!',
		'28'	=> 'Please activate the programme first',
		'403'	=> 'You may not view this section of the Station Management Software! If you feel that this is a mistake, then please contact the Station Administrator',
		'ACC'	=> 'You have Read Only privileges over this section.'
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
		'CONFIRM'	=> 'Confirmation',
		'SERIES'	=> 'Series',
		'ADDSER'	=> 'Add Series'
	);
	
	$ACTION = array(
		0			=> 'Logged In'
	);
	
	$STRING = array(
		'NEWUSER'	=> 'Access Password To Station',
		'NA'			=> 'Needs Assistance',
		'PP'			=> 'Pre Produced',
		'NONE'		=> 'None',
		'ACTIVE'	=> 'Active',
		'NOTACTIVE'=>'Not Active'
	);
	
	$CONFIRM = array(
		1			=> 'A new user has been created, you will be shortly redirected to the user management page!',
		2			=> 'A new programme has been added to the station management console, you will be shortly redirected to the programme overview page!',
		3			=> 'A new series has been added and filled with programmes for the defined period of time, you will be shortly redirected to the programme overview page!',
		4			=> 'Your personal settings have been updated, you will be shortly redirected to your settings management page!'
	);
?>