<?php // -*- tab-width: 2; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

	//	echo "<h3>Karbantartás miatt pár percig zárva.</h3><h3>Sorry, the server is currently under maintenance. Please try again later.</h3>"; exit;

//////////////////////////////////////////////////////////////////////////
require_once('functions.inc.php');
//////////////////////////////////////////////////////////////////////////

startTiming();	

//////////////////////////////////////////////////////////////////////////
require_once('config.inc.php');
//////////////////////////////////////////////////////////////////////////

// this is valid only until we have an SQL connection to get persistent vars
$config['debug'] = $config['debug'] ? true : false;
$config['debugType'] = 'later';	// 'now' for output to browser

/*
if($_COOKIE['debug']) {
  $config['debug'] = $_COOKIE['debug'] == 'yes';
  debug("debug set from cookie to", $config['debug']);
}
*/

ini_set("error_log", $config['logFile']);
ini_set("log_errors", true);
error_reporting (E_ALL ^ E_NOTICE);

if($config['debug']) {
     error_log("\n\n---------------------------------------------------------------------------------\n" .  getenv("REQUEST_URI") . "\n",3, $config['logFile']);
}

//logger('debug', $config['debug']);

// the base URL for the whole site
$config['rootUrl'] = 'http://' . myGetenv('SERVER_NAME') . $config['localPrefix'];
//$config['rootUrl'] = $config['localPrefix'];
//if(substr($config['rootUrl'], -1) != '/')
//	$config['rootUrl'] .= '/';

// The base URL for images
$config['imageUrl'] = $config['rootUrl'] . '/static';

$config['tmpDir'] = $config['wwwdir'] . '/tmp';
$config['tmpUrl'] = $config['rootUrl'] . '/tmp';
$config['cacheDir'] = $config['wwwdir'] . '/tmp/cache';
$config['cacheUrl'] =  $config['rootUrl'] . '/tmp/cache';

umask(0002);


// change include_path
// we need to put PEAR into the include_path if it's not there

$WIN = stristr(PHP_OS,"win")&&!stristr(PHP_OS,"darwin")?"WIN":""; 
$PATH_SEP = $WIN ? ";" : ":";

$oldPath = ini_get("include_path");
if(!strstr($oldPath, $config['peardir'])) {
	if($oldPath) {
		$newPath = $config['peardir'].$PATH_SEP.$oldPath;
	} else {
		//$newPath = $config['peardir'].$PATH_SEP.".";
		$newPath = $config['peardir'];
	}
	if(!ini_set("include_path", $newPath))
		die("Failed to set include_path!!");
}
if($config['debug']) {
	error_log("OS: ".PHP_OS, 0);
	error_log("WIN: ".$WIN, 0);
	error_log("INCLUDE_PATH: ". $oldPath, 0);
	error_log("CHANGED INCLUDE_PATH: ". ini_get("include_path"), 0);
}

// load system files

require($config['peardir'] . '/DB.php');
// change this if you want to use other DBMS not Postgres
require_once($config['peardir'] . '/DB/pgsql.php');
if($config['userDbType'] == 'mysql') {
	require_once($config['peardir'] . '/DB/mysql.php');
	require($config['classdir'] . '/db_Wrap_mysql.class.php');
}
require($config['smartydir'] . '/Smarty.class.php');
require($config['smartydir'] . '/Config_File.class.php');
require($config['classdir'] . '/db_Wrap.class.php');
require($config['classdir'] . '/sotf_Utils.class.php');
require($config['classdir'] . '/sotf_FileList.class.php');
require($config['classdir'] . '/sotf_AudioCheck.class.php');
require($config['classdir'] . '/sotf_User.class.php');
require($config['classdir'] . '/sotf_UserPrefs.class.php');
require($config['classdir'] . '/sotf_Page.class.php');
require($config['classdir'] . '/sotf_Object.class.php');
require($config['classdir'] . '/sotf_Vars.class.php');
require($config['classdir'] . '/sotf_Permission.class.php');
require($config['classdir'] . '/sotf_Repository.class.php');
require($config['classdir'] . '/sotf_Vocabularies.class.php');
require_once($config['classdir'] . '/' . $config['userDbClass'] . '.class.php');

//PEAR::setErrorHandling(PEAR_ERROR_TRIGGER);
//PEAR::setErrorHandling(PEAR_ERROR_DIE);

// create sotf database connection
$config['sqlDSN'] = 'pgsql://' . $config['nodeDbUser'] . ':' . $config['nodeDbPasswd'] . '@' . $config['nodeDbHost'] .':'. $config['nodeDbPort'] .'/'. $config['nodeDbName'];
debug("sqlDSN", $config['sqlDSN']);

$db = new db_Wrap;
$db->debug = $config['debug'];
$success = $db->makeConnection($config['sqlDSN'], false, 'node');
if (DB::isError($success))
{
  echo "Node DB connection failed: " . $success->getMessage();
	logError("Node DB connection failed", $config['sqlDSN']);
	die();
} 
$db->setFetchmode(DB_FETCHMODE_ASSOC);

if($config['selfUserDb']) {
	$userdb = &$db;
} else {
	// create user database connection
	$config['sqlUserDSN'] = $config['userDbType'] . '://' . $config['userDbUser'] .':'. $config['userDbPasswd'] .'@'. $config['userDbHost'] .':'. $config['userDbPort'] .'/'. $config['userDbName'];
	debug("sqlUserDSN", $config['sqlUserDSN']);

	if($config['userDbType'] == 'mysql') 
		$userdb = new db_Wrap_mysql;
	else
		$userdb = new db_Wrap;
	$userdb->debug = $config['debug'];
	$success = $userdb->makeConnection($config['sqlUserDSN'], false, 'user');
	if (DB::isError($success))
		{
			echo "User DB connection failed: " . $success->getMessage();
			logError("User DB connection failed", $config['sqlUserDSN']);
			die();
		}
	$userdb->setFetchmode(DB_FETCHMODE_ASSOC);
}

// persistent server variables
$sotfVars = new sotf_Vars($db, 'sotf_vars');

$config['debug'] = $sotfVars->get('debug', 1);

$userdb->debug = $sotfVars->get('debug_sql', 1);
$db->debug = $sotfVars->get('debug_sql', 1);

// start session
if(!headers_sent())
		 session_start();

if($config['debug'])
{
	error_log("",0);
  //error_log("-------------------------------------------------------------------", 0);
  error_log("REQUEST_URI: " . myGetenv("REQUEST_URI"), 0);
	error_log("REMOTE_HOST: " . getHostName() ,0);
  error_log("USER_AGENT: " . myGetenv('HTTP_USER_AGENT') ,0);
  error_log("REFERER: " . myGetenv('HTTP_REFERER'),0);
  error_log("HTTP_HOST: " . myGetenv('HTTP_HOST'),0);
  error_log("SERVER_NAME: " . myGetenv('SERVER_NAME'),0);
  error_log("HTTP_ACCEPT_LANGUAGE: " . myGetenv('HTTP_ACCEPT_LANGUAGE'),0);
  foreach($_GET as $key => $value) {
    debug("GET: $key", $value);
  }
  foreach($_POST as $key => $value) {
    debug("POST: $key", $value);
  }
  foreach($_COOKIE as $key => $value) {
    debug("COOKIE: $key", $value);
  }
  if(count($_SESSION) > 0) {
    foreach($_SESSION as $key => $value) {
      //debug("SESSION: $key",$value);
			error_log("SESSION: $key = $value",0);
    }
  }
  //  foreach($_ENV as $key => $value) {
  //  error_log("ENV: $key = $value",0);
  //}
}

// configure smarty for HTML output
$smarty = new Smarty;
$smarty->template_dir = $config['basedir'] . "/code/templates";
$smarty->compile_dir = $config['basedir'] . "/code/templates_c";
$smarty->config_dir = $config['basedir'] . "/code/configs";
$smarty->compile_check = $sotfVars->get('smarty_compile_check', 1);
$smarty->debugging = $sotfVars->get('debug_smarty', 0);
$smarty->show_info_include = $sotfVars->get('debug_smarty', 0);

// this object contains various utilities
$utils = new sotf_Utils;

// for easier access
$scriptUrl = mygetenv('SCRIPT_NAME');
debug('scripturl', $scriptUrl);

// page object is for request handling and page generation
$page = new sotf_Page;

///////////////////////////////////////////////////
// Handle language change
///////////////////////////////////////////////////
if($_GET['uilang']) {
	$page->setUILanguage($_GET['uilang']);
	$url = $_GET['okURL'];
	if(!$url) $url = $config['localPrefix'];
	$page->redirect($url);
	exit;
}

// we need trick for making pages indexed by Google
// therefore we pass some parameters in pathinfo
// after this call getParameter can be used to get these parameters as well
$pathinfoParamExceptions = array('getIcon','getJingle','getUserFile');
if(!in_array($page->action, $pathinfoParamExceptions)) {
	sotf_Utils::collectPathinfoParams();
}

// permissions object is for managing and asking for permissions
$permissions = new sotf_Permission;
//$permissions->debug = true;

// the repository of radio stations
$repository = new sotf_Repository($config['repositoryDir'], $db);

// all controlled vocabularies
$vocabularies = new sotf_Vocabularies($db);

// now you have the following global objects: $config, $db, $userdb, $smarty, $page, $repository, $user, $permission
// is that too many?

// forwarding all $config to smarty is a security risk
// $smarty->assign("CONFIG", $config);
// add basic variables to Smarty
$smarty->assign("NODEID", $config['nodeId']);
$smarty->assign("NODE_NAME", $config['nodeName']);
$smarty->assign("ROOT_URL", $config['rootUrl']);
//$smarty->assign("ROOT_URL", $config['localPrefix']);
$smarty->assign("IMAGE_URL", $config['imageUrl']);
$smarty->assign("CACHE_URL", $config['cacheUrl']);
$smarty->assign("PHP_SELF", mygetenv('PHP_SELF'));
$smarty->assign("ICON_HEIGHT", $config['iconHeight']);
$smarty->assign("ICON_WIDTH", $config['iconWidth']);
$smarty->assign("DEBUG", $config['debug']);
$smarty->assign("ACTION", $page->action);
$smarty->assign("LANG", $lang);
if ($page->loggedIn()) {
  $smarty->assign("loggedIn", '1');
  $smarty->assign("USERNAME", $user->name);
  $smarty->assign("PERMISSIONS", $permissions->currentPermissions);
  if($permissions->isEditor())
    $smarty->assign("IS_EDITOR", '1');
  //$smarty->assign("STATION_MANAGER", sotf_Permission::get("station_manager"));
}
if($config['debug']) {
  $smarty->assign("VIEWLOG", $page->logURL());
}
$smarty->assign("UI_LANGS", $config['outputLanguages']);
if(!empty($config['adminEmail'])) {
	$smarty->assign("ADMIN_EMAIL", $config['adminEmail']);
}

debug("action", $page->action);
debug("lang", $lang);
debug("userid", $user->id);

// character encoding tricks
/*
if(!ini_set('default_charset', 'UTF-8')) {
	logError("Could not change default charset");
}
debug("default_charset", ini_get('default_charset'));
*/

//if(!in_array($page->action, array('getFile','getIcon','getJingle','getUserFile','listen'))) {
if($page->action != 'install') {
	header("Content-Type: text/html; charset=UTF-8");
}

?>