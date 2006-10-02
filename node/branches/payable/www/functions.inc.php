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
  error_log("$host: ERROR: $msg. $private", 0);
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

function isGroupId($id) {
	return $id{0} == 'g';
}

function getGroupId($id) {
	if($id{0} == 'g')
		$id = substr($id, 1);
	debug("GETg", $id);
	return $id;
}

function getUserOrGroupName($id) {
	global $user;
	if(isGroupId($id))
		return sotf_Group::getGroupName($id);
	else
		return $user->getUsername($id);
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

//---------------- convert_special_chars() added by wolfi_fhstp ----------------------

function convert_special_chars($str){
	$tmp = "";
       for($i = 0; $i < strlen($str); $i++) {
           // alle durch _ ersetzte Zeichen
                           if((ord($str[$i]) >= 32 && ord($str[$i]) <= 45) ||
                                (ord($str[$i]) >= 58 && ord($str[$i]) <= 64) ||
                                        (ord($str[$i]) >= 91 && ord($str[$i]) <= 94) ||
                                                (ord($str[$i]) >= 123 && ord($str[$i]) <= 191) ||
                                                        (ord($str[$i]) == 47) || (ord($str[$i]) == 96) ||
                                                                (ord($str[$i]) == 247) || (ord($str[$i]) == 254) ||
                                                                        (ord($str[$i]) == 222) || (ord($str[$i]) == 240) ||
                                                                                (ord($str[$i]) == 215)) {
                                                        $tmp .= "_";
                                        }

                        else if((ord($str[$i]) >= 192 && ord($str[$i]) <= 197)) {
                                                        $tmp .= "A";
                                        }
                        else if((ord($str[$i]) >= 200 && ord($str[$i]) <= 203)){
                                                        $tmp .= "E";
                                        }
                        else if((ord($str[$i]) >= 204 && ord($str[$i]) <= 207)){
                                                        $tmp .= "I";
                                        }
                        else if((ord($str[$i]) >= 210 && ord($str[$i]) <= 213)){
                                                        $tmp .= "O";
                                        }
                        else if((ord($str[$i]) >= 217 && ord($str[$i]) <= 219)){
                                                        $tmp .= "U";
                                        }
                        else if((ord($str[$i]) >= 224 && ord($str[$i]) <= 227)) {
                                                        $tmp .= "a";
                                        }
                        else if((ord($str[$i]) >= 232 && ord($str[$i]) <= 235)){
                                                        $tmp .= "e";
                                        }
                        else if((ord($str[$i]) >= 236 && ord($str[$i]) <= 239)) {
                                                        $tmp .= "i";
                                        }
                        else if((ord($str[$i]) >= 242 && ord($str[$i]) <= 245)) {
                                                        $tmp .= "o";
                                        }
                        else if((ord($str[$i]) >= 249 && ord($str[$i]) <= 251)) {
                                                        $tmp .= "u";
                                        }


                        else {


               switch(ord($str[$i])) {

                               case 46:
                               $tmp .= "_";
                               break;
                                                case 198:
                               $tmp .= "Ae";
                               break;
                                                case 199:
                               $tmp .= "C";
                               break;
                                                case 208:
                               $tmp .= "D";
                               break;
                                                case 209:
                               $tmp .= "N";
                               break;
                                                case 214:
                               $tmp .= "Oe";
                               break;
                                                case 216:
                               $tmp .= "O";
                               break;
                                                case 220:
                               $tmp .= "Ue";
                               break;
                                                case 221:
                               $tmp .= "Y";
                               break;
                                                case 223:
                               $tmp .= "ss";
                               break;
                                                case 228:
                               $tmp .= "ae";
                               break;
                                                case 229:
                               $tmp .= "a";
                               break;
                                                case 230:
                               $tmp .= "ae";
                               break;
                                                case 231:
                               $tmp .= "c";
                               break;
                                                case 241:
                               $tmp .= "n";
                               break;
                                                case 246:
                               $tmp .= "oe";
                               break;
                                                case 248:
                               $tmp .= "o";
                               break;
                                                case 252:
                               $tmp .= "ue";
                               break;
                                                case 253:
                               $tmp .= "y";
                               break;
                                                case 255:
                               $tmp .= "y";
                               break;

                                                default:
                               $tmp .= (string)$str[$i];
                               break;
               }
       }
	
    }
	
    return($tmp);


}
//----------------------------------------------------------------------

?>