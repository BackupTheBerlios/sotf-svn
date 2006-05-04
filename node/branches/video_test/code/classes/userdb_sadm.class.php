<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

/*
 * Use this class if you are using the Self-Admin Toolkit for user management
 *
 * CUSTOMIZABLE CLASS: if you want to fit sotf users with another existing database
 * included by config.inc.php
 *
 * @author Andras Micsik SZTAKI DSD micsik@sztaki.hu, MODIFIED by Martin Schmidt ptmschmidt@fh-stpoelten.ac.at
 */

class userdb_sadm {

  var $db;
  var $userdb;

  function userdb_sadm() {
    global $db, $userdb;
    $this->userdb = & $userdb;
    $this->db = & $db;
  }

  // returns the current date/time in correct format for database
  function sqlDate() {
	 return $this->userdb->getSQLDate();
  }

  /** Modifies an existing user.  */
  function userDbUpdate($fields) {
    debug("userDbUpdate", $fields); // this will show the parameter array in the log
    if($fields['password']) {
      $query = sprintf("UPDATE authenticate SET passwd='%s' WHERE auth_id='%s'", $fields['password'], $fields['userid']);
      $this->userdb->query($query);
    }
    // RealName taken out: SET RealName=''
    $query = sprintf("UPDATE user_preferences SET language='%s' WHERE auth_id='%s'", $fields['language'], $fields['userid']);
    $this->userdb->query($query);
    // save email into sotf database, because sadm has no such field
    $query = sprintf("UPDATE sotf_user_prefs SET email='%s' WHERE id='%s'", $fields['email'], $fields['userid']);
    $this->db->query($query);
  }
  
  /** Creates a new user. */
  function userDbInsert($fields) {
    debug("userDbInsert", $fields); // this will show the parameter array in the log
    $query = sprintf("INSERT INTO authenticate (username,passwd,general_id,user_type) VALUES('%s','%s',1,'member')",
		     $fields['username'], $fields['password']);
    $this->userdb->query($query);
    // retrieve new id
    $query = sprintf("SELECT auth_id FROM authenticate WHERE username='%s'", $fields['username']);
    $id = $this->userdb->getOne($query);
    // save more user data
    // RealName taken out 
    $query = sprintf("INSERT INTO user_preferences (auth_id, language,last_visit,num_logins) VALUES('%s','%s','%s',1)",
		     $id, $fields['language'], $this->sqlDate() );
    $this->userdb->query($query);
    // save e-mail
    $query = sprintf("INSERT INTO sotf_user_prefs (id, username, email) VALUES('%s', '%s', '%s')",
		     $id, $fields['username'], $fields['email']);
    $this->db->query($query);
  }
  
  /** Deletes a user (by id) */
  function userDbDelete($fields) {
    // empty
  }
  
  function userDbSelect($fields) {
    if($fields['username']) {
      $query = sprintf("SELECT * FROM authenticate WHERE username='%s'", $fields['username']);
      $raw = $this->userdb->getRow($query);
      if (count($raw) > 0) {
		  $data['username'] = $raw['username'];
		  $data['userid'] = $raw['auth_id'];
		}
      return $data;
    }
    if($fields['userid']) {
      $query = sprintf("SELECT * FROM authenticate WHERE auth_id='%s'", $fields['userid']);
      $raw = $this->userdb->getRow($query);
      if (count($raw) > 0) {
	$data['username'] = $raw['username'];
	$data['userid'] = $raw['auth_id'];
      }		 
      // get some more data from sadm
      $query = sprintf("SELECT * FROM user_preferences WHERE auth_id='%s'", $fields['userid']);
      $raw = $this->userdb->getRow($query);
      if (count($raw) > 0) {
	$data['realname'] = $raw['RealName'];
	$data['language'] = $raw['language'];
      }		 
      // get e-mail
      $query = sprintf("SELECT email FROM sotf_user_prefs WHERE id='%s'", $fields['userid']);
      $data['email'] = $this->db->getOne($query);
      return $data;
    }
    raiseError("bad usage: userDbSelect");
  }
  
  function userDbLogin($fields) {
    $query = sprintf("SELECT auth_id, passwd FROM authenticate WHERE username='%s'", $fields['username']);
    $res = $this->userdb->getRow($query);
    if($res && $res['passwd'] == $fields['password']) {
      $id = $res['auth_id'];
      $query = sprintf("UPDATE user_preferences SET num_logins=num_logins+1, last_visit='%s' WHERE auth_id='%s'", 
		       $this->sqlDate(), $id);
      $this->userdb->query($query);
      return $id;
    } else
      return NULL;
  }


// ADDED BY Martin Schmidt 05-11-21
   function userCheckPwd($fields) {
    $query = sprintf("SELECT auth_id, passwd FROM authenticate WHERE username='%s'", $fields['username']);
    $res = $this->userdb->getRow($query);
    if($res && $res['password'] == $fields['password']) {
      return true;
    } else
      return false;
  }
  
    function getUserPwd($fields){
  	$query = sprintf("SELECT auth_id, passwd FROM authenticate WHERE username='%s'", $fields['username']);
    $res = $this->userdb->getRow($query);
	return $res['password'];
  }
  
 
// ----------------------------


  function userDbLogout($fields) {
  }
  
  /** Counts all registered users */
  function userDbCount() {
    return $this->userdb->getOne("SELECT count(*) FROM authenticate");  
  }
  
  /** Search for users. */
  function userDbFind($fields) {
    //$pattern, $prefix = false) {
    if($fields['prefix']) {
      $query = sprintf("SELECT auth_id AS userid, username FROM authenticate WHERE username ~* '^%s' ORDER BY username",
		       $fields['pattern']);
      $res = $this->userdb->getAssoc($query);
    } else {
      $query = sprintf("SELECT auth_id AS userid, username FROM authenticate WHERE username ~* '%s' ORDER BY username",
		       $fields['pattern']);
      $res = $this->userdb->getAssoc($query);
    }
    return $res;
  }
  
}

?>