<?php

function startTiming(){
  global $startTime;
  $microtime = microtime();
  $microsecs = substr($microtime, 2, 8);
  $secs = substr($microtime, 11);
  $startTime = "$secs.$microsecs";
}

startTiming();	
	
function stopTiming(){
  global $startTime, $totalTime;
  
  $microtime = microtime();
  $microsecs = substr($microtime, 2, 8);
  $secs = substr($microtime, 11);
  $endTime = "$secs.$microsecs";
  $totalTime = round(($endTime - $startTime),4);
  return $totalTime;
}

function dump($what, $name='')
{
	echo "<TABLE><TR><TD>";
	echo "<PRE>Dump: $name\n";
	print_r($what);
	echo "</PRE></TD></TR></TABLE>";
}

/** this creates a log entry if $debug is true*/
function debug($name, $msg='', $type='default') {
  global $debug, $debug_type;
  if ($debug) {
    // the $debug_type is set in config.inc.php
    if ($type == 'default') {
      $type = $debug_type;
    }
    if(is_array($msg)) {
      ob_start();
      //var_dump($msg);
      print_r($msg);
      $msg = "\n" . ob_get_contents();
      ob_end_clean();
    }
    error_log(getHostName() . ": $name: $msg", 0);
    if ($type == 'now' && headers_sent() ) {
      echo "<small><pre> Debug: $name: $msg </pre></small><br>\n";
    } 
  }
}

function getHostName()
{
	$host = getenv("REMOTE_HOST");
	if($host == "")
		$host = getenv("REMOTE_ADDR");
	return $host;
}

//////////////////////////////////////////////////////////////////////////
require_once('config.inc.php');
//////////////////////////////////////////////////////////////////////////

 
// these are ways to remotely switch debugging on
if($_GET['debug'] || $_POST['debug']) {
  error_log("Debugging turned on remotely!",0);
  $debug = true;		// true for on, false for off
  $debug_type = 'now';	// 'now' for output to browser
                                // 'log' for output to the admin log
  $sqlDebug = true;	// print all executed SQL statements into log
  $smartyDebug = true;	// enable compile check and debugging console for smarty
}
if($_COOKIE['debug']) {
  $debug = $_COOKIE['debug'] == 'yes';
  debug("debug set from cookie to", $debug);
}

ini_set("error_log", $logFile);
ini_set("log_errors", true);
error_reporting (E_ALL ^ E_NOTICE);

if($debug)
{
	error_log("\n---------------------------------------------------------------------------------\n" .  getenv("REQUEST_URI") . "\n",3, $logFile);
	error_log(getenv('REMOTE_HOST') . ": " . getenv('HTTP_USER_AGENT') ,0);
  error_log("REFERER: " . getenv('HTTP_REFERER'),0);
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
require($smartydir . '/Smarty.class.php');
require($smartydir . '/Config_File.class.php');
require($classdir . '/db_Wrap.class.php');
require($classdir . '/sotf_Utils.class.php');
require($classdir . '/sotf_FileList.class.php');
require($classdir . '/sotf_AudioCheck.class.php');
require($classdir . '/sotf_User.class.php');
require($classdir . '/sotf_Page.class.php');
require($classdir . '/sotf_Object.class.php');
require($classdir . '/sotf_Vars.class.php');
require($classdir . '/sotf_Permission.class.php');
require($classdir . '/sotf_Repository.class.php');

//PEAR::setErrorHandling(PEAR_ERROR_TRIGGER);
//PEAR::setErrorHandling(PEAR_ERROR_DIE);

function addError($msg) {
  global $page;
  if(DB::isError($msg)) 
    $msg = "SQL error: " . $msg->getMessage();
  debug("added error", $msg);
  $page->errors[] = $page->getlocalized($msg);
}

function raiseError($msg) {
  global $page;
  if(DB::isError($msg)) 
    $msg = "SQL error: " . $msg->getMessage();
  debug("raised error", $msg);
  $page->errors[] = $page->getlocalized($msg);
  $page->halt();
  exit;
}

function noErrors() {
  return empty($page->errors);
}

// this one is used from smarty to check permissions
function hasPerm($object, $perm) {
  global $permissions;
  return $permissions->hasPermission($object, $perm);
}


// create database connections

$sqlDSN = "pgsql://$nodeDbUser:$nodeDbPasswd@$nodeDbHost:$nodeDbPort/$nodeDbName";
$sqlUserDSN = "pgsql://$userDbUser:$userDbPasswd@$userDbHost:$userDbPort/$userDbName";

$db = db_Wrap::getDBConn($sqlDSN, false);
if (!$db or DB::isError($db))
{
  die ("Node DB connection failed: " . $db->getMessage());
} 
$db->setFetchmode(DB_FETCHMODE_ASSOC);

$userdb = db_Wrap::getDBConn($sqlUserDSN, false);
if (DB::isError($userdb))
{
  die ("User DB connection failed: " . $userdb->getMessage());
}
$userdb->setFetchmode(DB_FETCHMODE_ASSOC);

// configure smarty for HTML output
$smarty = new Smarty;
$smarty->template_dir = "$basedir/code/templates";
$smarty->compile_dir = "$basedir/code/templates_c";
$smarty->config_dir = "$basedir/code/configs";
$smarty->compile_check = $debug;
$smarty->debugging = $debug;
$smarty->show_info_include = $debug;

// this object contains various utilities
$utils = new sotf_Utils;

// page object is for request handling and page generation
$page = new sotf_Page;

// permissions object is for managing and asking for permissions
$permissions = new sotf_Permission;

// persistent server variables
$sotfVars = new sotf_Vars($db, 'sotf_vars');

// the repository of radio stations
$repository = new sotf_Repository($repositoryDir, $db);

// now you have the following global objects: $db, $userdb, $smarty, $page, $repository, $user, $permission


// add basic variables to Smarty
$smarty->assign("NODEID", $nodeId);
$smarty->assign("ROOTDIR", $rootdir);
$smarty->assign("IMAGEDIR", $imagedir);
$smarty->assign("CACHEDIR", $cacheprefix);
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

debug("action:", $page->action);
debug("lang", $lang);
debug("userid", $user->id);

?>