<?php // -*- tab-width: 2; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

//////////////////////////////////////////////////////////////////////////
require_once('functions.inc.php');
//////////////////////////////////////////////////////////////////////////

startTiming();	

//////////////////////////////////////////////////////////////////////////
require_once('config.inc.php');
//////////////////////////////////////////////////////////////////////////


// this is valid only until we have an SQL connection to get persistent vars
$debug = $debug ? false : true;
$debug_type = 'later';	// 'now' for output to browser

if($debug) {
     error_log("\n---------------------------------------------------------------------------------\n" .  getenv("REQUEST_URI") . "\n",3, $logFile);
}

/*
if($_COOKIE['debug']) {
  $debug = $_COOKIE['debug'] == 'yes';
  debug("debug set from cookie to", $debug);
}
*/

ini_set("error_log", $logFile);
ini_set("log_errors", true);
error_reporting (E_ALL ^ E_NOTICE);

//logger('debug', $debug);

// the base URL for the whole site
$rootdir = 'http://' . $_SERVER['HTTP_HOST'] . $localPrefix;
//$rootdir = $localPrefix;

// The base URL for images
$imagedir = $rootdir . '/static';

$tmpdir = $wwwdir . '/tmp';
$cachedir = $wwwdir . '/tmp/cache';
$cacheprefix =  $rootdir . '/tmp/cache';

umask(0002);

// load system files

/*
$os = getenv('OS');
//error_log("OS=$os",0);
if(preg_match("/windows/i", $os))
     $pathSep = ";";
     else
     $pathSep = ":";
     
$oldPath = ini_get("include_path");
$pathElements = array($peardir, $smartydir, $getid3dir, $classdir, $xmlrpcdir, '.', $oldPath);
$newPath = join($pathSep, $pathElements);
//error_log("PATH=$newPath",0);
if(!ini_set("include_path", $newPath))
	die("Failed to set include_path!!");
*/

require($peardir . '/DB.php');
// change this if you want to use other DBMS not Postgres
require_once($peardir . '/DB/pgsql.php');
require($smartydir . '/Smarty.class.php');
require($smartydir . '/Config_File.class.php');
require($classdir . '/db_Wrap.class.php');
require($classdir . '/sotf_Utils.class.php');
require($classdir . '/sotf_FileList.class.php');
require($classdir . '/sotf_AudioCheck.class.php');
require($classdir . '/sotf_User.class.php');
require($classdir . '/sotf_UserPrefs.class.php');
require($classdir . '/sotf_Page.class.php');
require($classdir . '/sotf_Object.class.php');
require($classdir . '/sotf_Vars.class.php');
require($classdir . '/sotf_Permission.class.php');
require($classdir . '/sotf_Repository.class.php');

//PEAR::setErrorHandling(PEAR_ERROR_TRIGGER);
//PEAR::setErrorHandling(PEAR_ERROR_DIE);

// create database connections

$sqlDSN = "pgsql://$nodeDbUser:$nodeDbPasswd@$nodeDbHost:$nodeDbPort/$nodeDbName";
$sqlUserDSN = "pgsql://$userDbUser:$userDbPasswd@$userDbHost:$userDbPort/$userDbName";

$db = new db_Wrap;
$db->debug = $debug;
$success = $db->makeConnection($sqlDSN, false);
if (DB::isError($success))
{
  die ("Node DB connection to $sqlDSN failed: \n" . $success->getMessage());
} 
$db->setFetchmode(DB_FETCHMODE_ASSOC);

$userdb = new db_Wrap;
$userdb->debug = $debug;
$success = $userdb->makeConnection($sqlUserDSN, false);
if (DB::isError($success))
{
  die ("User DB connection to $sqlUserDSN failed: \n" . $success->getMessage());
}
$userdb->setFetchmode(DB_FETCHMODE_ASSOC);

// persistent server variables
$sotfVars = new sotf_Vars($db, 'sotf_vars');

$debug = $sotfVars->get('debug', 1);

$userdb->debug = $sotfVars->get('debug_sql', 1);
$db->debug = $sotfVars->get('debug_sql', 1);

// start session
if(!headers_sent())
		 session_start();

if($debug)
{
  error_log("------------------------------------------", 0);
  error_log("REQUEST_URI: " . myGetenv("REQUEST_URI"), 0);
	error_log("REMOTE_HOST: " . getHostName() ,0);
  error_log("USER_AGENT: " . myGetenv('HTTP_USER_AGENT') ,0);
  error_log("REFERER: " . myGetenv('HTTP_REFERER'),0);
  foreach($_GET as $key => $value) {
    error_log("GET: $key = $value",0);
  }
  foreach($_POST as $key => $value) {
    error_log("POST: $key = $value",0);
  }
  foreach($_COOKIE as $key => $value) {
    error_log("COOKIE: $key = $value",0);
  }
  if(count($_SESSION) > 0) {
    foreach($_SESSION as $key => $value) {
      error_log("SESSION: $key = $value",0);
    }
  }
  //  foreach($_ENV as $key => $value) {
  //  error_log("ENV: $key = $value",0);
  //}
}

// configure smarty for HTML output
$smarty = new Smarty;
$smarty->template_dir = "$basedir/code/templates";
$smarty->compile_dir = "$basedir/code/templates_c";
$smarty->config_dir = "$basedir/code/configs";
$smarty->compile_check = $sotfVars->get('smarty_compile_check', 0);
$smarty->debugging = $sotfVars->get('debug_smarty', 0);
$smarty->show_info_include = $sotfVars->get('debug_smarty', 0);

// this object contains various utilities
$utils = new sotf_Utils;

// page object is for request handling and page generation
$page = new sotf_Page;

// permissions object is for managing and asking for permissions
$permissions = new sotf_Permission;

// the repository of radio stations
$repository = new sotf_Repository($repositoryDir, $db);

// now you have the following global objects: $db, $userdb, $smarty, $page, $repository, $user, $permission

// add basic variables to Smarty
$smarty->assign("NODEID", $nodeId);
$smarty->assign("ROOTDIR", $rootdir);
$smarty->assign("IMAGEDIR", $imagedir);
$smarty->assign("CACHEDIR", $cacheprefix);
$smarty->assign("ICON_HEIGHT", $iconHeight);
$smarty->assign("ICON_WIDTH", $iconWidth);
$smarty->assign("DEBUG", $debug);
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
if($debug) {
  $smarty->assign("VIEWLOG", $page->logURL());
}

debug("action", $page->action);
debug("lang", $lang);
debug("userid", $user->id);

?>