<?php // -*- tab-width: 2; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

function startTiming(){
  global $startTime;
  $microtime = microtime();
  $microsecs = substr($microtime, 2, 8);
  $secs = substr($microtime, 11);
  $startTime = "$secs.$microsecs";
}

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

/** this creates a log entry */
function logError($msg) {
  error_log(getHostName() . ": ERROR: $msg", 0);
}

/** this creates a log entry if $config['debug'] is true*/
function debug($name, $msg='', $type='default') {
  global $config;
  // the $config['debug_type'] is set in config.inc.php
  if ($config['debug']) {
    logger($name, $msg, $type);
  }
}

/** this creates a log entry */
function logger($name, $msg='', $type='default') {
	global $config;
  if ($type == 'default') {
    $type = $config['debug_type'];
  }
  if(is_array($msg)) {
    ob_start();
    var_dump($msg);
    //print_r($msg);
    $msg = "\n" . ob_get_contents();
    ob_end_clean();
  }
  error_log(getHostName() . ": $name: $msg", 0);
  if ($type == 'now' && headers_sent() ) {
    echo "<small><pre> Debug: $name: $msg </pre></small><br>\n";
  } 
}

function getHostName()
{
	if(!$host) $host = myGetenv("REMOTE_HOST");
	if(!$host) $host = myGetenv("REMOTE_ADDR");
	return $host;
}

function myGetenv($name) {
	$foo = getenv($name);
	if(!$foo)
		$foo = $_SERVER[$name];
	return $foo;
}

function addError($msg) {
  global $page;
  if(DB::isError($msg)) 
    $msg = "SQL error: " . $msg->getMessage();
  logError($msg);
  $page->errors[] = $page->getlocalized($msg);
}

function raiseError($msg) {
  global $page;
  if(DB::isError($msg)) 
    $msg = "SQL error: " . $msg->getMessage();
  logError($msg);
  $page->errors[] = $page->getlocalized($msg);
  $page->halt();
  exit;
}

function noErrors() {
  return empty($page->errors);
}

/** shortcut for permission check: hasPerm(<objectId>, <permName1>, <permName2>, ...)
will return true if the current user has at least one of the listed permissions for the object.
Also used in smarty templates to check permissions. */
function hasPerm($objectId) {
  global $permissions;
	$perm_list = func_get_args();
	for ($i = 1; $i <count($perm_list); $i++) {
		$perm = $permissions->hasPermission($objectId, $perm_list[$i]);
		debug("checking for permission " . $perm_list[$i] . " on " . $objectId, $perm);
		if($perm)
			return true;
	}
	return false;
}

function checkPerm($objectId) {
  global $page, $permissions;
	$perm_list = func_get_args();
	for ($i = 1; $i <count($perm_list); $i++) {
		$permName = $perm_list[$i];
		$perm = $permissions->hasPermission($objectId, $permName);
		debug("checking for permission " . $permName . " on " . $objectId, $perm);
		if($perm)
			return;
	}
	for ($i = 1; $i <count($perm_list); $i++) {
		if($i > 1)
			$permTransl = $permTransl . ' ' . $page->getlocalized('or') . ' ';
		$permTransl = $permTransl . $page->getlocalized('perm_' . $perm_list[$i]);
	}
	$msg = $page->getlocalizedWithParams('no_permission', $permTransl);
	raiseError($msg);
}

/** shortcut for permission check: hasAnyPerm(<objectId>)
will return true if the current user has some kind of permission for the object.
Also used in smarty templates to check permissions. */
function hasAnyPerm($object) {
  global $permissions;
	return $permissions->hasAnyPermission($object);
}

/** wrapper function for move_uploaded_file, because sometimes chmod is needed afterwards. */
function moveUploadedFile($fieldName, $file) {
  if(!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $file))
		raiseError("Could not move uploaded file from " . $_FILES[$fieldName]['tmp_name'] . " to $file");
	//debug("Moved uploaded file", $_FILES[$fieldName]['tmp_name'] . " to $file");
  if(!chmod($file, 0660)) {
		logger("Could not chmod file $file!");
	}
}

?>