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
	include('config.inc.php');																# guest what this is ;)
	include('classes/error.class.php');												# Error Collector
	include('classes/user.class.php');												# User Session Wrapper
	include('classes/navBar.class.php');											# Navigation Bar Processor
	include('classes/helpBox.class.php');											# Help Box Generator
	include('classes/log.class.php');													# action log module
	include('functions/eh.inc.php');													# Override PHP's Error's handling routines
	include('functions/timers.inc.php');											# Timers to reveal bottlenecks
	include('functions/prepend.inc.php');											# Smarty pre and postpend data for pop-up windows
	require(SMARTY_PATH . '/Smarty.class.php');								# Template Processor
	require(PEAR_PATH . 'DB.php');														# Pear DB Object .::. http://pear.php.net/
	
	//Massive Initializer
	$myError = new error();																		# Initialize an empty 'error bin'
	$myNav = new navBar(HOME_NAME,SRC_ROOT);									# Create an instance of the navigation bar
	$smarty = new Smarty;																			# Initialize Template Parser
	$myLog = new logBuilder();																# Initialize Log builder
		
	//initialization of private error handling routine
	set_error_handler('eh');
	
	//session stuff
	session_start();
	
	//smarty global assignments
	$smarty->assign("root",SRC_ROOT . TPL_DIR . "/");					# assigning where the path to templates
	$smarty->assign("server_root",SRC_ROOT);									# assigning the path to root links
	
	if($_SERVER['HTTP_ACCEPT_LANGUAGE']=='en'){								# choosing the right language file
		$smarty->assign("lang","en");
		include("configs/errors.en.php");
	}else{
		$smarty->assign("lang","en");
		include("configs/errors.en.php");
	}
	
	$smarty->assign("build_id","36");													# assigning a 'build id'
	
	//build database connections
	$db = DB::connect(array(																	# Start a connection to the database
    'phptype'  => DB_TYPE,
    'dbsyntax' => false,
    'protocol' => false,
    'hostspec' => DB_HOST,
    'database' => DB_NAME,
    'username' => DB_USER,
    'password' => DB_PASS
	));
	
	//did the connection to local database fail?
	if(DB::isError($db)){
		trigger_error($ERR[3]);																	# trigger error
		exit;																										# stop page processing
	}
	
	/**
	 * authorize()
	 * 
	 * check to see whether this user may work with this section of the web site
	 * 
	 * @param $section (string)
	 * @return (bool||redirect)
	 */
	function authorize($section){
		global $ERR, $myError;
		if($_SESSION['USER']->get($section)==2){					# user has full access to this section
			//nothing
			return true;
		}else if($_SESSION['USER']->get($section)==1){		# user has read only access to this section
			if((count($_POST) > 0) or (isset($_GET['action']))){		# there has been a POST call or an action
				//unset the action calls
				unset($_POST);
				unset($_GET['action']);
			}
			
			//add error to 'error bin'
			$myError->add($ERR['403']);
			
			//notify client of problem ;)
			return false;
		}else{																						# user has absolutely no access to this section
			//redirect to no access page
			header('Location: noaccess.php');
			exit;
		}
	}
	
	//start page generation timer
	startTiming();
?>