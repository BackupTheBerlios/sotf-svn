<?php 
// -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

/**
* 
*
* @author Andras Micsik - micsik@sztaki.hu
*/
class sotf_NodeObjectWithPerm extends sotf_NodeObject {

	/** current users permissions on this object */
	var $permissions;

	function sotf_NodeObjectWithPerm($tablename, $id='', $data='') {
		debug("constructor", 'sotf_NodeObjectWithPerm');
		$this->sotf_NodeObject($tablename, $id, $data);
		//$this->loadPermissions();
	}						

  function create() {
    global $user, $permissions;
    parent::create(); // the new unique id is generated here
    $this->addPermission($user->id, 'admin');
  }

	// TODO: Read permissions in the constructor of the soft_user
	//       and this function should look them, instead of SQL call
	function hasPermission($perm) {
		global $permissions;
    return $permissions->hasPermission($this->id, $perm);
	}

	function addPermission($userid, $perm) {
    global $permissions;
    $permissions->addPermission($this->id, $userid, $perm);
	}

	function delPermission($userid, $perm) {
    global $permissions;
    $permissions->delPermission($this->id, $userid, $perm);
	}

}
