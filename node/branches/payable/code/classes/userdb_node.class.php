<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

/*
 * Use this class if you are using the node itself for user management, no external database
 *
 * CUSTOMIZABLE CLASS: if you want to fit sotf users with another existing database
 * included by config.inc.php
 *
 * @author Andras Micsik SZTAKI DSD micsik@sztaki.hu, MODIFIED by Martin Schmidt ptmschmidt@fh-stpoelten.ac.at
 */

class userdb_node {

  var $db;
  var $userdb;

  function userdb_node() {
    global $db;
    $this->userdb = & $db;
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
      $query = sprintf("UPDATE sotf_users SET password='%s' WHERE id='%s'", $fields['password'], $fields['userid']);
      $this->userdb->query($query);
    }
    // RealName taken out: SET RealName=''
    $query = sprintf("UPDATE sotf_users SET language='%s', email='%s' WHERE id='%s'", $fields['language'], $fields['email'], $fields['userid']);
    $this->userdb->query($query);
    // save email into sotf database, because sadm has no such field
    $query = sprintf("UPDATE sotf_user_prefs SET email='%s' WHERE id='%s'", $fields['email'], $fields['userid']);
    $this->db->query($query);
  }
  
  /** Creates a new user. */
  function userDbInsert($fields) {
    debug("userDbInsert", $fields); // this will show the parameter array in the log
    $query = sprintf("INSERT INTO sotf_users (username,password,language,email, last_visit) VALUES('%s','%s','%s','%s','%s')",
							$fields['username'], $fields['password'], $fields['language'], $fields['email'], $this->sqlDate());
    $this->userdb->query($query);
    // retrieve new id
    $query = sprintf("SELECT id FROM sotf_users WHERE username='%s'", $fields['username']);
    $id = $this->userdb->getOne($query);
    // save e-mail once more: silly thing because of old sadm conventions
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
      $query = sprintf("SELECT * FROM sotf_users WHERE username='%s'", $fields['username']);
      $raw = $this->userdb->getRow($query);
      if (count($raw) > 0) {
		  $data['username'] = $raw['username'];
		  $data['userid'] = $raw['id'];
		}
      return $data;
    }
    if($fields['userid']) {
      $query = sprintf("SELECT * FROM sotf_users WHERE id='%s'", $fields['userid']);
      $raw = $this->userdb->getRow($query);
      if (count($raw) > 0) {
		  $data['username'] = $raw['username'];
		  $data['userid'] = $raw['id'];
		  //$data['realname'] = $raw['realname'];
		  $data['language'] = $raw['language'];
		  $data['email'] = $raw['email'];
      }		 
      return $data;
    }
    raiseError("bad usage: userDbSelect");
  }
  
  function userDbLogin($fields) {
    $query = sprintf("SELECT id, password FROM sotf_users WHERE username='%s'", $fields['username']);
    $res = $this->userdb->getRow($query);
    if($res && $res['password'] == $fields['password']) {
      $id = $res['id'];
      $query = sprintf("UPDATE sotf_users SET num_logins=num_logins+1, last_visit='%s' WHERE id='%s'", 
		       $this->sqlDate(), $id);
      $this->userdb->query($query);
      return $id;
    } else
      return NULL;
  }

// ADDED BY Martin Schmidt 05-11-21
   function userCheckPwd($fields) {
    $query = sprintf("SELECT password FROM sotf_users WHERE username='%s'", $fields['username']);
    $res = $this->userdb->getRow($query);
    if($res && $res['password'] == $fields['password']) {
      return true;
    } else
      return false;
  }
  
  function getUserPwd($fields){
  	$query = sprintf("SELECT password FROM sotf_users WHERE username='%s'", $fields['username']);
    $res = $this->userdb->getRow($query);
	return $res['password'];
  }
// ----------------------------


  function userDbLogout($fields) {
  }
  
  /** Counts all registered users */
  function userDbCount() {
    return $this->userdb->getOne("SELECT count(*) FROM sotf_users");  
  }
  
  /** Search for users. */
  function userDbFind($fields) {
    //$pattern, $prefix = false) {
    if($fields['prefix']) {
      $query = sprintf("SELECT id AS userid, username FROM sotf_users WHERE username ~* '^%s' ORDER BY username",
		       $fields['pattern']);
      $res = $this->userdb->getAssoc($query);
    } else {
      $query = sprintf("SELECT id AS userid, username FROM sotf_users WHERE username ~* '%s' ORDER BY username",
		       $fields['pattern']);
      $res = $this->userdb->getAssoc($query);
    }
    return $res;
  }
  
}

?>