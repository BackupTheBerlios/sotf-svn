<?php 
// -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

/**
* This is a class for handling users
*
* @author Andras Micsik SZTAKI DSD micsik@sztaki.hu
*/
class sotf_User
{
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

	/**
	* Constructor
	*
	* @constructor	sotf_User
	* @param	string	$name	name of user account
	* @access	public
	*/
	function sotf_User($id = "")
	{
			global $page, $db, $userdb;
			
		if ($id)
		{
			// find user in sadm
      $data = $userdb->getRow("SELECT * FROM authenticate WHERE auth_id = '$id'");
      if (count($data) == 0) {
				$this->exist = false;
				return;
			}
	
			$this->name = $data['username'];
			$this->id = $data['auth_id'];
			$id = $this->id;
      
      // get some more data from sadm
			$data = $userdb->getRow("SELECT * FROM user_preferences WHERE auth_id = '$id'");
      debug("user_preferences", $data);
			$this->realname = $data['RealName'];
			$this->language = $data['language'];
			$this->email = $data['email'];
			$this->exist = true;

      // get e-mail
      $this->email = $db->getOne("SELECT email FROM sotf_user_prefs WHERE id='$id'");
      // user permissions are stored in $permission
		}
	}

  // TODO: when deleting user delete from all tables (no foreign key)
  function delete() {
  }
	
	function getUserDir() {
		global $userDirs;
		$dir = "$userDirs/" . $this->name;
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
			if ($f == "." || $f == "..")
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

	function deleteFile($filename)
	{
    $targetFile =  sotf_Utils::getFileInDir($this->getUserDir(), $filename);
    if (unlink($targetFile))
      return 0;
    else
      raiseError("Could not remove file $targetFile");
	}

	function userNameCheck($username) {
		global $userdb, $page;
		$data = $userdb->getOne("SELECT username FROM authenticate WHERE username='". sotf_Utils::magicQuotes($username) . "'");
		if($data)
			return $page->getlocalized("username_in_use");
		return false;
	}

	function save($password) {
		global $userdb;
		if($password) {
			$pwdChange = " ,password='". sotf_Utils::magicQuotes($password) . "' ";
			$query = "UPDATE authenticate SET passwd='". sotf_Utils::magicQuotes($password) . "' WHERE auth_id='" . sotf_Utils::magicQuotes($this->id) . "'";
			$userdb->query($query);
		}
    // RealName taken out
		//$query = "UPDATE user_preferences SET RealName='". sotf_Utils::magicQuotes($this->realname) ."', language='". sotf_Utils::magicQuotes($this->language) . "' WHERE auth_id='" . sotf_Utils::magicQuotes($this->id) . "'";
    $query = "UPDATE user_preferences SET language='". sotf_Utils::magicQuotes($this->language) . "' WHERE auth_id='" . sotf_Utils::magicQuotes($this->id) . "'";
		$userdb->query($query);
    $this->saveEmail();
	}

  /** saves email as in field, e-mails are stored in sotf_user_prefs as a workaround */
  function saveEmail() {
    global $db;
    // TODO instead of magicquotes, check e-mail format??
    $email = sotf_Utils::magicQuotes($this->email);
    $db->query("UPDATE sotf_user_prefs SET email='$email' WHERE id='$this->id'");
    debug('rows', $db->affectedRows());
    if($db->affectedRows()==0)
      $db->query("INSERT INTO sotf_user_prefs (id, username, email) VALUES('$this->id', '$this->name', '$this->email')");
  }

  /** static */
	function register($password, $name, $realname, $language, $email) {
		// TODO: check not to change user name!!
		global $userdb, $db, $page;
		if(strlen($name)==0) {
			debug("USERDB", "attempt to register with empty userid");
			return $page->getlocalized("invalid_username");
		}
		debug("USERDB", "registering user: ". $name);
		$name = sotf_Utils::magicQuotes($name);
		$passwd = sotf_Utils::magicQuotes($password);
		$query = "INSERT INTO authenticate (username,passwd,general_id,user_type) VALUES('$name','$password',1,'member')";
		$userdb->query($query);
		$id = $userdb->getOne("SELECT auth_id FROM authenticate WHERE username='$name'");
		//		$query = "INSERT INTO user_preferences (RealName,language,last_visit,num_logins) ";
    // RealName taken out 						. sotf_Utils::magicQuotes($realname) . "','" 
		$query = "INSERT INTO user_preferences (auth_id, language,last_visit,num_logins) ";
		$query .= "VALUES('$id','" . sotf_Utils::magicQuotes($language) 
			. "','". db_Wrap::getSQLDate() . "',1)";
		$userdb->query($query);
    // TODO: check email??
    $email = sotf_Utils::magicQuotes($email);
    $db->query("INSERT INTO sotf_user_prefs (id, username, email) VALUES('$id', '$name', '$email')");
	}

	function login($name, $password)
	{
		global $user, $userdb, $page;

		$res = $userdb->getRow("SELECT auth_id, passwd FROM authenticate WHERE username='".sotf_Utils::magicQuotes($name)."'");
		if(DB::isError($res))
      raiseError($res);
		if($res['passwd'] != $password)
		{
				error_log("Login failed for $name from ". getHostName(), 0);
				return $page->getlocalized("invalid_login");
		}
		else
		{
			$user = new sotf_User($res['auth_id']);
      debug("Login successful", $user->name . ' = ' . $user->id );
			$userdb->query("UPDATE user_preferences SET num_logins=num_logins+1, last_visit='" . db_Wrap::getSQLDate() . "' WHERE auth_id='" . $user->id . "' ");
			$_SESSION['currentUserId'] = $user->id;
		}
	}
	
	function logout() {
		global $user;
    debug("user logout", $user->name . ' = ' . $user->id );
		$user = '';
		$_SESSION['currentUserId'] = '';
	}

	function listUsers() {
		global $userdb;
		return $userdb->getCol("SELECT username FROM authenticate ORDER BY username");
	}

	function countUsers() {
		global $userdb;
		return $userdb->getOne("SELECT count(*) FROM authenticate");
	}

	function getUsername($user_id) {
		global $userdb;
		if (is_numeric($user_id))
			return $userdb->getOne("SELECT username FROM authenticate WHERE auth_id = $user_id");
		return false;
	}

	function getUserid($username) {
		global $userdb;
		return $userdb->getOne("SELECT auth_id FROM authenticate WHERE username = '$username'");
	}
  
  /** Returns the URL for the FTP access to the users personal upload directory. */
  function getUrlForUserFTP() {
    global $userFTP;
    if(substr($userFTP, -1) != '/')
      $userFTP = $userFTP . '/';
    $userFtpUrl = str_replace('ftp://', "ftp://".$this->name."@" , $userFTP . $this->name);
    return $userFtpUrl;
  }

  /** Get user preferences */
  function getPreferences() {
    global $db;
    if(isset($this->preferences))
      return $this->preferences;
    $this->preferences = sotf_UserPrefs::load($this->id);
    return $this->preferences;
	}
  


}
?>