<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/

	/************************
	* Initialize the framework :: 07.11.2002
	*----------------------------------------
	* The basic purpose of this file is to include all the needed
	* classes and initialize these for further work with them 
	* inside the code.
	************************/

	//Massive Includer
	include('classes/error.class.php');					# Error Collector
	include('classes/user.class.php');					# User Session Wrapper
	include('classes/navBar.class.php');				# Navigation Bar Processor
	include('functions/eh.inc.php');						# Override PHP's Error's handling routines
	include('functions/timers.inc.php');				# Timers to reveal bottlenecks
	include('functions/prepend.inc.php');				# Smarty pre and postpend data for pop-up windows
	require(SMARTY_PATH . 'Smarty.class.php');	# Template Processor
	require(PEAR_PATH . 'DB.php');							# Pear DB Object .::. http://pear.php.net/
	
	//Massive Initializer
	$myError = new error();											# Initialize an empty 'error bin'
	$myNav = new navBar(HOME,SRC_ROOT);					# Create an instance of the navigation bar
	$smarty = new Smarty;												# Initialize Template Parser
	
	$db = DB::connect(array(										# Start a connection to the database
    'phptype'  => DB_TYPE,
    'dbsyntax' => false,
    'protocol' => false,
    'hostspec' => DB_HOST,
    'database' => DB_NAME,
    'username' => DB_USER,
    'password' => DB_PASS
	));
	
	//initialization of private error handling routine
	set_error_handler('eh');
	
	//session stuff
	session_start();
	
	//smarty global assignments
	$smarty->assign("root",SRC_ROOT . TPL_DIR . "/");
	$smarty->assign("server_root",SRC_ROOT);
	
	//start page generation times
	startTiming();
?>