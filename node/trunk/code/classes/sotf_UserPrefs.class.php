<?php 
// -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

/**
* This is a class for storing and handling user preferences
*
* @author Andras Micsik SZTAKI DSD micsik@sztaki.hu
*/
class sotf_UserPrefs
{

	/**
	* Numeric id of the user.
	* @var	$id	string
	*/
	var $id;

	/**
	* Saved queries of the user (array of array).
	*/
	var $savedQueries = array();

  /** this will clear default query or queries, so the user will have normal home page with list of new programmes */
  function clearDefaultQuery() {
    
  }

  function getDefaultQuery() {
    reset($this->savedQueries);
    while(list(,$query)=each($this->savedQueries)) {
      if($query['default'])
        return $query['query'];
    }
    return '';
  }

  function save() {
    global $db, $user;
    $data = serialize($this);
    $count = $db->getOne("SELECT count(*) FROM sotf_user_prefs WHERE id = '$this->id'");
    if($count==1)
      $db->query("UPDATE sotf_user_prefs SET prefs='$data' WHERE id = '$this->id'");
    else {
      $name = sotf_Utils::magicQuotes($user->name);
      $db->query("INSERT INTO sotf_user_prefs (id, username, prefs) VALUES('$user->id','$name','$data')");
    }
  }

  /** static */
  function load($id) {
    global $db;
    if(empty($id))
      raiseError("empty id for user prefs");
    $data = $db->getOne("SELECT prefs FROM sotf_user_prefs WHERE id = '$id'");
    if(empty($data)) {
      $prefs = new sotf_UserPrefs;
      $prefs->id = $id;
    } else {
      $prefs = unserialize($data);
      if($prefs === FALSE)
        raiseError("Could not unserialize user preferences");
    }
    return $prefs;
  }

}
?>