<?php

function startTiming(){
  global $startTime;
  $microtime = microtime();
  $microsecs = substr($microtime, 2, 8);
  $secs = substr($microtime, 11);
  $startTime = "$secs.$microsecs";
}

startTiming();	
	
/**
 * stopTiming() - to stop the timer for script execution
 * 
 * @package	StreamOnTheFly
 * @return	float	end time - float
 * 
 * Version: 1.0  Date: 13.01.2002  Author: Koulikov Alexey
 */
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
      var_dump($msg);
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
include('config.inc.php');
//////////////////////////////////////////////////////////////////////////

 
// this is a way to remotely switch debugging on
if($_GET['debug'] || $_POST['debug']) {
  error_log("Debugging turned on remotely!",0);
  $debug = true;		// true for on, false for off
  $debug_type = 'now';	// 'now' for output to browser
                                // 'log' for output to the admin log
  $sqlDebug = true;	// print all executed SQL statements into log
  $smartyDebug = true;	// enable compile check and debugging console for smarty
}

ini_set("error_log", $logFile);
ini_set("log_errors", true);
error_reporting (E_ALL ^ E_NOTICE);

if($debug)
{
	error_log("\n---------------------------------------------------------------------------------\n" .  getenv("REQUEST_URI") . "\n",3, $logFile);
	error_log(getenv('REMOTE_HOST') . ": " . getenv('HTTP_USER_AGENT') ,0);
}

// the base URL for the whole site
$rootdir = 'http://' . $_SERVER['HTTP_HOST'] . $localPrefix;
//$rootdir = $localPrefix;

// The base URL for images
$imagedir = $rootdir . '/static';


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
require($classdir . '/error_Control.class.php');
require($classdir . '/sotf_Utils.class.php');
require($classdir . '/sotf_User.class.php');
require($classdir . '/sotf_Page.class.php');
require($classdir . '/sotf_Permission.class.php');
require($classdir . '/sotf_Id.class.php');
require($classdir . '/sotf_Vars.class.php');
require($classdir . '/sotf_Repository.class.php');
require($classdir . '/sotf_FileList.class.php');
require($classdir . '/sotf_AudioCheck.class.php');

//PEAR::setErrorHandling(PEAR_ERROR_TRIGGER);
//PEAR::setErrorHandling(PEAR_ERROR_DIE);

$errorControl = new error_Control;

function addError($msg) {
  global $errorControl;
  $errorControl->add($msg);
}

function raiseError($msg) {
  global $errorControl;
  debug("raised error", $msg);
  $errorControl->raise($msg);
}

// create database connections
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

// smarty for HTML output
$smarty = new Smarty;
$smarty->template_dir = "$basedir/code/templates";
$smarty->compile_dir = "$basedir/code/templates_c";
$smarty->config_dir = "$basedir/code/configs";
$smarty->compile_check = $debug;
$smarty->debugging = $debug;
$smarty->show_info_include = $debug;
$smarty->assign("NODEID", $nodeId);
$smarty->assign("ROOTDIR", $rootdir);
$smarty->assign("IMAGEDIR", $imagedir);
$smarty->assign("DEBUG", $debug);

// page object is for request handling and page generation
$page = new sotf_Page;

// persistent server variables
$sotfVars = new sotf_Vars($db, 'sotf_vars');

// the repository of radio stations
$repository = new sotf_Repository($repositoryDir, $db);

// now you have the following global objects: $db, $userdb, $smarty, $page, $repository

?>