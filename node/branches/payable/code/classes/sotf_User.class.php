<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

/** 
* This is a class for basic handling of users. Preferences and
* playlists are handled in separate classes.
*
* @author Andras Micsik SZTAKI DSD micsik@sztaki.hu, Martin Schmidt ptmschmidt@fh-stpoelten.ac.at
*/

class sotf_User {

	/**
	* Numeric id of the user. Used only in sadm.
	* @var	$id	string
	*/
	var $id;

	/**
	* Name of the user account
	* @var	$name	string
	*/
	var $name;

	/**
	* Real name of the user
	* @var	$realname	string
	*/
	var $realname;

	/**
	* Preferred language of the user
	* @var	$name	string
	*/
	var $language;

	/**
	* E-mail address of the user
	* @var	$email	string
	*/
	var $email;

	/**
	* True if this is an existing user
	* @var	$exist	string
	*/
	var $exist;

	/**
	* User preferences
	* @var	$preferences	Object
	*/
	var $preferences;

	function &getStorageObject() {
	  global $config;
	  static $object;
	  if(!$object) {
		 $object = & new $config['userDbClass'];
		 if(!is_object($object))
			raiseError("Could not instantiate class: " . $config['userDbClass']);
	  }
	  return $object;
	}

	/**
	* Constructor
	*
	* @constructor	sotf_User
	* @param	string	$name	name of user account
	* @access	public
	*/
	function sotf_User($id = "") {
	  global $page, $config;
	  
	  if ($id) {
		 $storage = &sotf_User::getStorageObject();
		 $data = $storage->userDbSelect(array('userid'=>$id));
		 if(empty($data['userid'])) {
			$this->exist = false;
			return;
		 }
		 
		 $this->name = $data['username'];
		 $this->id = $data['userid'];
		 $this->realname = $data['realname'];
		 $this->language = $data['language'];
		 $this->email = $data['email'];
		 $this->exist = true;
		 // user permissions are stored in $permission
	  }
	}

	// TODO: when deleting user delete from all tables (no foreign key)
	function delete() {
	  $storage = &sotf_User::getStorageObject();
	  $storage->userDbDelete(array('id' => $this->id));
	}

	/** Checks if the given user name is already in use by someone else. */
	function userNameCheck($username) {
	  global $page;
	  $storage = &sotf_User::getStorageObject();
	  $data = $storage->userDbSelect(array('username' => sotf_Utils::magicQuotes($username)));
	  if($data)
		 return $page->getlocalized("username_in_use");
	  return false;
	}

	/** Saves the current user data. If user password is given as parameter, then it is changed. */
	function save($password='') {
	  if($password)
		 $fields['password'] = sotf_Utils::magicQuotes($password);
	  $fields['userid'] = $this->id;
	  $fields['username'] = sotf_Utils::magicQuotes($this->name);
	  $fields['language'] = sotf_Utils::magicQuotes($this->language);
	  $fields['realname'] = sotf_Utils::magicQuotes($this->realname);
	  $fields['email'] = sotf_Utils::magicQuotes($this->email);
	  $storage = &sotf_User::getStorageObject();
	  $storage->userDbUpdate($fields);
	}
	
	/** static method: Register new user with given data. */
	function register($password, $name, $realname, $language, $email) {
	  // TODO: check not to change user name!!
	  /// TODO: check if user name is unique!
	  global $page;
	  $storage = &sotf_User::getStorageObject();
	  if(strlen($name)==0) {
		 debug("USERDB", "attempt to register with empty userid");
		 return $page->getlocalized("invalid_username");
	  }
	  debug("USERDB", "registering user: ". $name);
	  $name1 = sotf_Utils::makeValidName($name, 32);
	  if ($name1 != $name) {
		 //$page->addStatusMsg('illegal_name');
		 return $page->getlocalized("illegal_name");
	  }
	  $fields['password'] = sotf_Utils::magicQuotes($password);
	  $fields['username'] = sotf_Utils::magicQuotes($name);
	  $fields['language'] = sotf_Utils::magicQuotes($language);
	  $fields['realname'] = sotf_Utils::magicQuotes($realname);
	  $fields['email'] = sotf_Utils::magicQuotes($email);
	  $storage->userDbInsert($fields);
	}
	
	function login($name, $password) {
	  global $user, $page;
	  $storage = &sotf_User::getStorageObject();
	  $fields['password'] = sotf_Utils::magicQuotes($password);
	  $fields['username'] = sotf_Utils::magicQuotes($name);
	  $id = $storage->userDbLogin($fields);
	  if(!$id) {
		 error_log("Login failed for $name from ". getHostName(), 0);
		 return $page->getlocalized("invalid_login");
	  } else {
		 $user = new sotf_User($id);
		 debug("Login successful", $user->name . ' = ' . $user->id );
		 $_SESSION['currentUserId'] = $user->id;
	  }
	}
	
	function logout() {
	  global $user;
	  $storage = &sotf_User::getStorageObject();
	  $storage->userDbLogout(array('userid', $user->id));
	  debug("user logout", $user->name . ' = ' . $user->id );
	  $user = '';
	  $_SESSION['currentUserId'] = '';
	}

	/** static: Returns the name of the user given with ID. */
	function getUsername($user_id) {
	  global $userdb;
	  static $userNameCache;
	  $storage = &sotf_User::getStorageObject();
	  if (is_numeric($user_id)) {
		 if($userNameCache[$user_id])
			return $userNameCache[$user_id];
		 $data = $storage->userDbSelect(array('userid' => sotf_Utils::magicQuotes($user_id)));
		 if(!$data)
			return false;
		 $name = $data['username'];
		 $userNameCache[$user_id] = $name;
		 return $name;
	  }
	  return false;
	}
	
	/** static: Retrieves userid for a username. */
	function getUserid($username) {
	  global $userdb;
	  $storage = &sotf_User::getStorageObject();
	  $data = $storage->userDbSelect(array('username' => sotf_Utils::magicQuotes($username)));
	  if(!$data)
		 return NULL;
	  return $data['userid'];
	}
	
	/** Returns the URL for the FTP access to the users personal upload directory. */
	function getUrlForUserFTP() {
	  global $config;
	  if(substr($config['userFTP'], -1) != '/')
		 $config['userFTP'] = $config['userFTP'] . '/';
	  $userFtpUrl = str_replace('ftp://', "ftp://".$this->name."@" , $config['userFTP']);
	  return $userFtpUrl;
	}
	
	/** Get user preferences: returns a class of type sotf_UserPrefs containing all preferences data for the user. */
	function getPreferences() {
	  global $db;
	  if(isset($this->preferences))
		 return $this->preferences;
	  $this->preferences = sotf_UserPrefs::load($this->id);
	  return $this->preferences;
	}
	
	/*****************************************************************
	 *
	 *    HANDLING of USERS' FILES
	 *
	 *****************************************************************/
	
	function getUserDir() {
	  global $config;
	  $dir = $config['userDirs'] . "/" . $this->name;
	  if(!is_dir($dir)) {
		 if(!mkdir($dir, 0775))
			raiseError("Could not create directory for user");
         }
	  return $dir;
	}
	
	function getUserFiles() {
	  $dir = $this->getUserDir();
	  $handle = opendir($dir) or die("could not open user dir: $dir");
	  while (false!==($f = readdir($handle))) {
		 if ($f == "." || $f == ".." || $f == ".quota" )
			continue;
		 if(is_dir($f))
			continue;
		 $list[] = $f;
	  }
	  closedir($handle);
	  if ($list)
		 sort($list);
	  return $list;
	}
	
	function deleteFile($filename) {
	  $targetFile =  sotf_Utils::getFileInDir($this->getUserDir(), $filename);
	  if (unlink($targetFile))
		 return 0;
	  else
		 raiseError("Could not remove file $targetFile");
	}

	/*****************************************************************
	 *
	 *    FUNCTIONS on ALL USERS
	 *
	 *****************************************************************/
	
	/** Count all users. */
	function countUsers($pattern = '') {
	  $storage = &sotf_User::getStorageObject();
    $pattern = sotf_Utils::magicQuotes($pattern);
	  return $storage->userDbCount($pattern);
	}
	
	/** Search for users. */
	function findUsers($pattern, $prefix = false) {
	  global $userdb;
	  $storage = &sotf_User::getStorageObject();
	  $fields['pattern'] = sotf_Utils::magicQuotes($pattern);
	  if($prefix)
		 $fields['prefix'] = 1;
	  $res = $storage->userDbFind($fields);
	  if(DB::isError($res))
		 raiseError($res);
	  return $res;
	}

	function listUsers($start, $hitsPerPage, $pattern) {
		global $userdb;
		$storage = &sotf_User::getStorageObject();
		$pattern = sotf_Utils::magicQuotes($pattern);
		$list = $storage->userDbList($start, $hitsPerPage, $pattern);
    return $list;
	}

}
?>