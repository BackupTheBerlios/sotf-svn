<?php

//////////////////////////////////////////////////////////////////////////
require_once('functions.inc.php');
//////////////////////////////////////////////////////////////////////////

startTiming();	

//////////////////////////////////////////////////////////////////////////
require_once('config.inc.php');
//////////////////////////////////////////////////////////////////////////

// this is valid only until we have an SQL connection to get persistent vars
//$debug = $debug ? false : true;
if(!isset($debug)) $debug = true;
$debug_type = 'later';	// 'now' for output to browser

if($debug) {
     error_log("\n---------------------------------------------------------------------------------\n" .  myGetenv("REQUEST_URI") . "\n",3, $logFile);
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

logger('debug', $debug);

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

require($peardir . '/DB.php');
// change this if you want to use other DBMS not Postgres
require_once($peardir . '/DB/pgsql.php');
require($smartydir . '/Smarty.class.php');
require($smartydir . '/Config_File.class.php');
require($classdir . '/db_Wrap.class.php');
require($classdir . '/sotf_Utils.class.php');
require($classdir . '/sotf_Page.class.php');
require($classdir . '/sotf_Vars.class.php');

//PEAR::setErrorHandling(PEAR_ERROR_TRIGGER);
//PEAR::setErrorHandling(PEAR_ERROR_DIE);

// create database connections

$sqlDSN = "pgsql://$nodeDbUser:$nodeDbPasswd@$nodeDbHost:$nodeDbPort/$nodeDbName";

$db = new db_Wrap;
$db->debug = $debug;
$success = $db->makeConnection($sqlDSN, false);
if (DB::isError($success))
{
  die ("Portal DB connection to $sqlDSN failed: \n" . $success->getMessage());
} 
$db->setFetchmode(DB_FETCHMODE_ASSOC);

// persistent server variables
$sotfVars = new sotf_Vars($db, 'portal_vars');

$debug = $sotfVars->get('debug', 1);

$userdb->debug = $sotfVars->get('debug_sql', 1);
$db->debug = $sotfVars->get('debug_sql', 1);

if($debug)
{
  error_log("------------------------------------------", 0);
  error_log("REQUEST_URI: " . myGetenv("REQUEST_URI"), 0);
	error_log("REMOTE_HOST: " . myGetenv('REMOTE_HOST') ,0);
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

// now you have the following global objects: $db, $smarty, $page

// add basic variables to Smarty
$smarty->assign("ROOTDIR", $rootdir);
$smarty->assign("IMAGEDIR", $imagedir);
$smarty->assign("CACHEDIR", $cacheprefix);
$smarty->assign("DEBUG", $debug);
$smarty->assign("ACTION", $page->action);
$smarty->assign("LANG", $lang);
if($debug) {
  $smarty->assign("VIEWLOG", $page->logURL());
}

debug("action:", $page->action);
debug("lang", $lang);

?>