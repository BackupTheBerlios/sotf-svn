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
function logError($msg, $private='') {
	global $config;
	$email = $config['adminEmail'];
	$host = getHostName();
  error_log("$host: ERROR: $msg. ($private)", 0);
	if($email && $config['sendMailAboutErrors'] && $msg != 'no_such_object' )
		mail($email, "SOTF error - $host", "$host: $msg\n$private");
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
  if(is_array($msg) || is_object($msg)) {
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

function addError($msg, $private='') {
  global $page;
  if(DB::isError($msg)) {
		$private .= ' - ' . $msg->getMessage();
    $msg = "SQL error"; 
	}
  logError($msg, $private);
	if(!strstr($msg, ' '))
		 $msg = $page->getlocalized($msg);
  $page->errors[] = $msg;
}

function raiseError($msg, $private='') {
  global $page;
  if(DB::isError($msg)) {
		$private .= ' - ' . $msg->getMessage();
    $msg = "SQL error"; 
	}
  logError($msg, $private);
	if(!strstr($msg, ' '))
		 $msg = $page->getlocalized($msg);
  $page->errors[] = $msg;
  $page->halt();
  exit;
}

function noErrors() {
  return empty($page->errors);
}

/** shortcut for permission check: hasPerm(<mixed>, <permName1>, <permName2>, ...)
where <mixed> can be objectId, object, or array of object data fields,
will return true if the current user has at least one of the listed permissions for the object.
Also used in smarty templates to check permissions. */
function hasPerm($objectId) {
  global $permissions;
	$perm_list = func_get_args();
	for ($i = 1; $i <count($perm_list); $i++) {
		if(hasPermPrivate($objectId, $perm_list[$i]))
			return true;
	}
	return false;
}

/** same as hasPerm, except that it gives an error message and halts. */
function checkPerm($objectId) {
  global $page, $permissions;
	$perm_list = func_get_args();
	for ($i = 1; $i <count($perm_list); $i++) {
		$permName = $perm_list[$i];
		if(hasPermPrivate($objectId, $permName))
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

/** private!! */
function hasPermPrivate($mixed, $permName) {
  global $permissions, $repository;
	// mixed can be object, field array or object_id or 'node'
	if(is_object($mixed)) {
		$fields = $mixed->getAll();
	} elseif(is_array($mixed)) {
		$fields = $mixed;
	} elseif($mixed == 'node') {
		return $permissions->hasPermission('node', $permName);
	} else {
		$obj = & $repository->getObject($mixed);
		if(!$obj)
			raiseError("Database inconsistency: no such object: $mixed");
		$fields = $obj->getAll();
	}
	// check perm on the object itself
	if($permissions->hasPermission($fields['id'], $permName))
		return true;
	// inherited from station
	if($fields['station_id']) {
		if($permissions->hasPermission($fields['station_id'], $permName))
			return true;
	}
	// inherited from series
	if($fields['series_id']) {
		if($permissions->hasPermission($fields['series_id'], $permName))
			return true;
	}
	// node admins are quite like Unix root
	if($permissions->hasPermission('node', $permName))
		return true;
	return false;
}

/** wrapper function for move_uploaded_file, because sometimes chmod is needed afterwards. */
function moveUploadedFile($fieldName, $file) {
	// check and convert filename
	$trans = array("'" => "", '"' => '', '..' => '', );
	//$file = strtr(urldecode(stripslashes(urldecode($file))), $trans);
	$file = strtr(stripslashes($file), $trans);
	// move file to final location
  if(!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $file))
		raiseError("Could not move uploaded file from " . $_FILES[$fieldName]['tmp_name'] . " to $file");
	//debug("Moved uploaded file", $_FILES[$fieldName]['tmp_name'] . " to $file");
  if(!chmod($file, 0660)) {
		logger("Could not chmod file $file!");
	}
}

function excludeRobots() {
	global $smarty;
	$smarty->append('META_TAGS', '<META NAME="ROBOTS" CONTENT="NOINDEX,NOFOLLOW">');
}

function checkAdminAccess() {
	global $config;
	$host = getHostName();
	debug('admin check', $config['adminDomain']);
	if(!preg_match('/' . $config['adminDomain'] . '/i', $host))
		raiseError("no access", "to admin page: " . myGetenv("REQUEST_URI"));
	else
		debug("admin access OK for", $host); 
}

?>