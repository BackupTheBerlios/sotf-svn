<?php //-*- tab-width: 3; indent-tabs-mode: 1; -*-

/**
* This is a class for handling users
*
* @author Andras Micsik SZTAKI DSD <micsik@sztaki.hu>
* @version 0.1
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
	* Per station permissions of the user
	* @var	$permissions	string
	*/
	var $permissions;

	/**
	* Global permissions of the user
	* @var	$permissionsGlobal	string
	*/
	var $permissionsGlobal;

	/**
	* True if this is an existing user
	* @var	$exist	string
	*/
	var $exist;

	/**
	* Constructor
	*
	* @constructor	sotf_User
	* @param	string	$name	name of user account
	* @access	public
	*/
	function sotf_User($name = "")
	{
	    global $page, $db, $userdb;
	    
		if ($name)
		{
			// find user in userdb
		    $data = $userdb->getRow("SELECT * FROM authenticate WHERE username = '$name'");
		    if (count($data) == 0)
			{
				$this->exist = false;
				return;
			}
	
			$this->name = $name;
			$this->id = $data['auth_id'];
			$id = $this->id;
	
		    // get some more data from sadm
			$data = $userdb->getRow("SELECT * FROM user_preferences WHERE auth_id = $id");
			$this->realname = $data['realname'];
			$this->language = $data['language'];
			$this->email = $data['email'];
			$this->exist = true;
		    
			// load permissions for user
			$this->permissionsGlobal = 
	        $db->getCol("SELECT permission FROM sotf_user_global_groups, sotf_group_permission, sotf_permissions " .
							  "WHERE sotf_user_global_groups.group_id = sotf_group_permission.group_id " .
							  " AND sotf_user_global_groups.user_id = '$id' " . 
							  " AND sotf_group_permission.permission_id = sotf_permissions.id");
			
			//select station_id, permission from sotf_station_access a, sotf_group_permission g, sotf_permissions p WHERE a.user_id=1 AND a.group_id=g.group_id AND g.permission_id=p.id;

			$result = $db->query("SELECT DISTINCT station FROM sotf_user_group WHERE username = '$name'");
			while ($row = $result->fetchRow())
			  {
				 if (($row['station'] != NULL) && ($row['station'] != ''))
					{
					  $stations[] = $row['station'];
					}
			  }
			if ($stations) {
			  foreach ($stations as $station) {
					$groups[$station] = $db->getCol("SELECT group_id FROM sotf_user_group WHERE username = '$name' AND station='$station'");
				 }
			  while (list($stationname, $station) = each($groups)) {
				 foreach($station as $group) {
					$this->permissions[$stationname] = $db->getCol("SELECT permission FROM sotf_group_permission WHERE group_id='$group'");
				 }
			  }
			}
		}
		// TODO: load user profile
	}

  function isEditor() {
    return(isset($this->permissions) && count($this->permissions) > 0);
  }
	
	function getUserDir() {
		global $userDirs;
		$dir = "$userDirs/" . $this->name;
		if(!is_dir($dir))
			mkdir($dir, 0775);
    return $dir;
	}

	function getUserFiles() {
		$dir = $this->getUserDir();
		$handle = opendir($dir) or die("could not open user dir: $dir");
		while (false!==($f = readdir($handle))) {
			if ($f == "." || $f == "..")
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
		$filename = sotf_Utils::getFileFromPath($filename);
	    $targetFile = $this->getUserDir() . '/'. $filename;
	    if (unlink($targetFile))
	    	return 0;
	    else
	    	raiseError("Could not remove file $targetFile");
	}

// --- innen 
	
	function userNameCheck($username) {
	  global $userdb, $page;
	  $data = $userdb->getOne("SELECT username FROM authenticate WHERE username='". sotf_Utils::clean($username) . "'");
	  if($data)
	    return $page->getlocalized("username_in_use");
	  return false;
	}

	function save($password) {
	  global $userdb;
	  if($password) {
	    $pwdChange = " ,password='". sotf_Utils::clean($password) . "' ";
      $query = "UPDATE authenticate SET passwd='". sotf_Utils::clean($password) . "' WHERE auth_id='" . sotf_Utils::clean($this->id) . "'";
      $userdb->query($query);
    }
	  $query = "UPDATE user_preferences SET RealName='". sotf_Utils::clean($this->realname) ."', language='". sotf_Utils::clean($this->language) . "' WHERE auth_id='" . sotf_Utils::clean($this->id) . "'";
      //"', email='". sotf_Utils::clean($this->email) . "' $pwdChange WHERE username='" . sotf_Utils::clean($this->name) ."' ";
	  $userdb->query($query);
	}

	function register($password, $name, $realname, $language, $email) {
    // TODO: check not to change user name!!
    global $userdb, $page;
	  if(strlen($name)==0) {
	    debug("USERDB", "attempt to register with empty userid");
	    return $page->getlocalized("invalid_username");
	  }
	  debug("USERDB", "registering user: ". $name);
    $name = sotf_Utils::clean($name);
    $passwd = sotf_Utils::clean($password);
	  $query = "INSERT INTO authenticate (username,passwd) VALUES('$name','$password')";
    $userdb->query($query);
    $id = $userdb->getOne("SELECT auth_id FROM authenticate WHERE username='$name'");
    //	  $query = "INSERT INTO user_preferences (RealName,language,last_visit,num_logins) ";
    $query = "INSERT INTO user_preferences (auth_id, realname, language,last_visit,num_logins) ";
	  $query .= "VALUES('$id','"
      	    . sotf_Utils::clean($realname) . "','" 
      . sotf_Utils::clean($language) 
	    // . "','" . sotf_Utils::clean($email) 
	    . "','". db_Wrap::getSQLDate() . "',1)";
	  $userdb->query($query);
	}

	function login($name, $password)
	{
		global $user, $userdb, $page;

		$pwd = $userdb->getOne("SELECT passwd FROM authenticate WHERE username='".sotf_Utils::clean($name)."'");
		if(DB::isError($pwd))
			raiseError("could not compare passwords");
		if($pwd != $password)
		{
		    error_log("Login failed for $name from ". getHostName(), 0);
		    return $page->getlocalized("invalid_login");
		}
		else
		{
			$user = new sotf_User($name);
			$userdb->query("UPDATE user_preferences SET num_logins=num_logins+1, last_visit='" . db_Wrap::getSQLDate() . "' WHERE auth_id='" . $user->id . "' ");
			$_SESSION['username'] = $user->name;
		}
	}
	
  function logout() {
    global $user;
    $user = '';
    $_SESSION['username'] = '';
  }

	function listUsers() {
	  global $userdb;
	  return $userdb->getCol("SELECT username FROM authenticate");
	}

}
?>