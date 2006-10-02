<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id: sotf_User.class.php 548 2006-04-03 16:21:28Z buddhafly $

/**
 * This is customizable
 *
 * @author Andras Micsik 
 */

class sotf_UserData extends sotf_Object {
  
  function sotf_UserData($id='', $data='') {
    $this->sotf_Object('sotf_user_data', $id, $data);
  }

  /** static */
  function saveData($userid) {
    global $_POST;
    $o = new sotf_UserData();
    $o->set('user_id', $userid);
    $o->find();
	 $o->copyPostData();
    $o->save();
  }

  /** static */
  function getSmartyData($userid) {
    $o = new sotf_UserData();
	 if($o->hasPostData()) {
		$o->copyPostData();
	 } elseif($userid) {
		$o->set('user_id', $userid);
		$o->find();
	 }
	 return $o->getAll();
  }

  function hasPostData() {
	 global $_POST;
	 foreach($_POST as $name => $value) {
      if(preg_match('/^ud_(\w+?)_(.*)$/', $name)) 
		  return true;
	 }
	 return false;
  }

  /** private */
  function copyPostData() {
    global $_POST;
    foreach($_POST as $name => $value) {
      if(preg_match('/^ud_(\w+?)_(.*)$/', $name, $mm)) {
		  $type = $mm[1];
		  $field = $mm[2];
		  $this->set($field, $value);
      }
    }
  }

}

?>