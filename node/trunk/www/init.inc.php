<?php // -*- tab-width: 2; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

//echo "<h3>Sorry, the server is currently under maintenance. Please try again later.</h3>"; exit;

//////////////////////////////////////////////////////////////////////////
require_once('functions.inc.php');
//////////////////////////////////////////////////////////////////////////

startTiming();	

//////////////////////////////////////////////////////////////////////////
require_once('config.inc.php');
//////////////////////////////////////////////////////////////////////////


// this is valid only until we have an SQL connection to get persistent vars
$config['debug'] = $config['debug'] ? false : true;
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
$config['rootUrl'] = 'http://' . $_SERVER['HTTP_HOST'] . $config['localPrefix'];
//$config['rootUrl'] = $config['localPrefix'];

// The base URL for images
$config['imageUrl'] = $config['rootUrl'] . '/static';

$config['tmpDir'] = $config['wwwdir'] . '/tmp';
$config['cacheDir'] = $config['wwwdir'] . '/tmp/cache';
$config['cacheUrl'] =  $config['rootUrl'] . '/tmp/cache';

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
$pathElements = array($config['peardir'], $config['smartydir'], $config['getid3dir'], $config['classdir'], $config['xmlrpcdir'], '.', $oldPath);
$newPath = join($pathSep, $pathElements);
//error_log("PATH=$newPath",0);
if(!ini_set("include_path", $newPath))
	die("Failed to set include_path!!");
*/

require($config['peardir'] . '/DB.php');
// change this if you want to use other DBMS not Postgres
require_once($config['peardir'] . '/DB/pgsql.php');
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

///////////////////////////////////////////////////
// Handle language change
///////////////////////////////////////////////////
if($_GET['uilang']) {
	if(!setcookie('uiLang', $_GET['uilang'])) {
		die("could not set cookie for uilang");
	}
	$url = $_GET['okURL'];
	if(!$url) $url = $config['localPrefix'];
	header ("Location: " . $url);
	exit;
}

//PEAR::setErrorHandling(PEAR_ERROR_TRIGGER);
//PEAR::setErrorHandling(PEAR_ERROR_DIE);

// create database connections

$config['sqlDSN'] = 'pgsql://' . $config['nodeDbUser'] . ':' . $config['nodeDbPasswd'] . '@' . $config['nodeDbHost'] .':'. $config['nodeDbPort'] .'/'. $config['nodeDbName'];
debug("sqlDSN", $config['sqlDSN']);
$config['sqlUserDSN'] = 'pgsql://' . $config['userDbUser'] .':'. $config['userDbPasswd'] .'@'. $config['userDbHost'] .':'. $config['userDbPort'] .'/'. $config['userDbName'];
debug("sqlUserDSN", $config['sqlUserDSN']);


$db = new db_Wrap;
$db->debug = $config['debug'];
$success = $db->makeConnection($config['sqlDSN'], false);
if (DB::isError($success))
{
  die ("Node DB connection to " . $config['sqlDSN'] . " failed: \n" . $success->getMessage());
} 
$db->setFetchmode(DB_FETCHMODE_ASSOC);

$userdb = new db_Wrap;
$userdb->debug = $config['debug'];
$success = $userdb->makeConnection($config['sqlUserDSN'], false);
if (DB::isError($success))
{
  die ("User DB connection to " . $config['sqlUserDSN'] . " failed: \n" . $success->getMessage());
}
$userdb->setFetchmode(DB_FETCHMODE_ASSOC);

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
  error_log("-------------------------------------------------------------------", 0);
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

// we need trick for making pages indexed by Google
// therefore we pass some parameters in pathinfo
// after this call getParameter can be used to get these parameters as well
$pathinfoParamExceptions = array('getFile','getIcon','getJingle','getUserFile');
if(!in_array($page->action, $pathinfoParamExceptions)) {
	sotf_Utils::collectPathinfoParams();
}

// permissions object is for managing and asking for permissions
$permissions = new sotf_Permission;
$permissions->debug = true;

// the repository of radio stations
$repository = new sotf_Repository($config['repositoryDir'], $db);

// now you have the following global objects: $config, $db, $userdb, $smarty, $page, $repository, $user, $permission
// is that too many?

// forwarding all $config to smarty is a security risk
// $smarty->assign("CONFIG", $config);
// add basic variables to Smarty
$smarty->assign("NODEID", $config['nodeId']);
$smarty->assign("NODE_NAME", $config['nodeName']);
//$smarty->assign("ROOT_URL", $config['rootUrl']);
$smarty->assign("ROOT_URL", $config['localPrefix']);
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

debug("action", $page->action);
debug("lang", $lang);
debug("userid", $user->id);

?>