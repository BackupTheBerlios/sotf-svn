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

/** this creates a log entry if $debug is true*/
function debug($name, $msg='', $type='default') {
  global $debug, $debug_type;
  // the $debug_type is set in config.inc.php
  if ($debug) {
    logger($name, $msg, $type);
  }
}

/** this creates a log entry */
function logger($name, $msg='', $type='default') {
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

// this one is used from smarty to check permissions
function hasPerm($object, $perm) {
  global $permissions;
  return $permissions->hasPermission($object, $perm);
}


?>