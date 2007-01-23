<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id: userdb_typo3.class.php 337 2003-12-01 12:45:59Z andras $

/*
 * Use this class if you want to use Typo3 for user management
 *
 * CUSTOMIZABLE CLASS: if you want to fit sotf users with another existing database
 * included by config.inc.php
 *
 * @author Andras Micsik SZTAKI DSD micsik@sztaki.hu
 * @modified Wolfgang Reutz Vorarlberg University of Applied Sciences for Typo3 CMS support
 */

class userdb_typo3 {

  var $db;
  var $userdb;

  function userdb_typo3() {
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
      $query = sprintf("UPDATE fe_users SET password='%s', email='%s' WHERE uid='%s'", $fields['password'], $fields['email'], $fields['userid']);
      $this->userdb->query($query);
    } else {
    $query = sprintf("UPDATE fe_users SET email='%s' WHERE uid='%s'", $fields['email'], $fields['userid']);
     }
      $this->userdb->query($query);
    // RealName taken out: SET RealName=''
    $query = sprintf("UPDATE fe_users_sotf SET language='%s' WHERE uid='%s'", $fields['language'], $fields['userid']);
    $this->userdb->query($query);
  }
  
  /** Creates a new user. */
  function userDbInsert($fields) {
    debug("userDbInsert", $fields); // this will show the parameter array in the log
    $query = sprintf("INSERT INTO fe_users (username,password,email) VALUES('%s','%s','%s')",
		     $fields['username'], $fields['password'], $fields['email']);
    $this->userdb->query($query);
    // retrieve new id
    $query = sprintf("SELECT uid FROM fe_users WHERE username='%s'", $fields['username']);
    $id = $this->userdb->getOne($query);
    // save more user data
    // RealName taken out 
    $query = sprintf("INSERT INTO fe_users_sotf (uid, language,last_visit,num_logins) VALUES('%s','%s','%s',1)",
		     $id, $fields['language'], $this->sqlDate() );
    $this->userdb->query($query);
  }
  
  /** Deletes a user (by id) */
  function userDbDelete($fields) {
    // empty
  }
  
  function userDbSelect($fields) {
    if($fields['username']) {
      $query = sprintf("SELECT * FROM fe_users WHERE username='%s'", $fields['username']);
      $raw = $this->userdb->getRow($query);
      if (count($raw) > 0) {
		  $data['username'] = $raw['username'];
		  $data['userid'] = $raw['uid'];
		}
      return $data;
    }
    if($fields['userid']) {
      $query = sprintf("SELECT * FROM fe_users WHERE uid='%s'", $fields['userid']);
      $raw = $this->userdb->getRow($query);
      if (count($raw) > 0) {
	$data['username'] = $raw['username'];
	$data['userid'] = $raw['uid'];
	$data['realname'] = $raw['name'];
	$data['email'] = $raw['email'];
      }		 
      // get some more data from sadm
      $query = sprintf("SELECT * FROM fe_users_sotf WHERE uid='%s'", $fields['userid']);
      $raw = $this->userdb->getRow($query);
      if (count($raw) > 0) {
	$data['language'] = $raw['language'];
      }		 
      return $data;
    }
    raiseError("bad usage: userDbSelect");
  }
  
  function userDbLogin($fields) {
    $query = sprintf("SELECT uid, password FROM fe_users WHERE username='%s'", $fields['username']);
    $res = $this->userdb->getRow($query);
    if($res && $res['password'] == $fields['password']) {
      $id = $res['uid'];
      $query = sprintf("UPDATE fe_users_sotf SET num_logins=num_logins+1, last_visit='%s' WHERE uid='%s'", $this->sqlDate(), $id);
      $this->userdb->query($query);
      return $id;
    } else
      return NULL;
  }

  function userDbLogout($fields) {
  }
  
  /** Counts all registered users from typo3 */
  function userDbCount() {
    return $this->userdb->getOne("SELECT count(*) FROM fe_users");  
  }
  
  /** Search for users. */
  function userDbFind($fields) {
    //$pattern, $prefix = false) {
        if($fields['prefix']) {
          $query = "SELECT uid AS userid, username FROM fe_users WHERE username LIKE '".$fields['pattern']."%' ORDER BY username";
          $res = $this->userdb->getAssoc($query);
        } else {
          $query = "SELECT uid AS userid, username FROM fe_users WHERE username LIKE '%".$fields['pattern']."%' ORDER BY username";
          $res = $this->userdb->getAssoc($query);
        }
    return $res;
  }
  
}

?>