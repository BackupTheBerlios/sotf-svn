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
		$this->loadPermissions();
	}						

  function create() {
    global $user;
    $this->addPermission('admin', $user->id);
    return parent::create();
  }

	function loadPermissions() {
		global $user;
		if($user && $user->id) {
			$id = $this->id;
			$userid = $user->id;
			$this->permissions = $this->db->getCol("SELECT permission FROM sotf_permissions s, sotf_user_permissions u WHERE u.object_id = '$id' AND u.user_id = '$userId' AND u.permission_id = s.id");
			if(DB::isError($this->permissions))
				raiseError($this->permissions);
		}
	}

	// TODO: Read permissions in the constructor of the soft_user
	//       and this function should look them, instead of SQL call
	function hasPermission($perm) {
		global $user;
		if($user)
			if ($this->db->getOne("SELECT sotf_user_permissions.permission_id FROM sotf_user_permissions, sotf_permissions WHERE sotf_user_permissions.user_id = '$user->id' AND sotf_user_permissions.object_id = '$this->id' AND (sotf_permissions.permission = 'admin' OR sotf_permissions.permission = '$perm')"))
				return true;
		return false;
	}

	function addPermission($perm, $userid) {
		if(!is_numeric($userid) || $userid < 1)
			raiseError("Invalid user id: '$userid'");
		$permission_id = $this->db->getOne("SELECT id FROM sotf_permissions WHERE permission='$perm'");
		$this->db->query("INSERT INTO sotf_user_permissions (user_id, object_id, permission_id) VALUES($userid, '$this->id', $permission_id)");
	}

	function delPermission($perm, $userid) {
		if(!is_numeric($userid) || $userid < 1)
			raiseError("Invalid user id: '$userid'");
		$permission_id = $this->db->getOne("SELECT id FROM sotf_permissions WHERE permission='$perm'");
		$this->db->query("DELETE FROM sotf_user_permissions WHERE user_id = $userid and object_id = '$this->id' AND permission_id = $permission_id");
	}

	function listUsersAndPermissions() {
		return $this->db->getAll("SELECT sotf_user_permissions.user_id, sotf_permissions.permission FROM sotf_user_permissions, sotf_permissions WHERE sotf_permissions.id = sotf_user_permissions.permission_id");
	}

}
