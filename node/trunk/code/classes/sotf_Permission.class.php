<?php
// -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

/**
* This is a class for handling permossions.
*
* @author	Tamas Kezdi SZTAKI DSD <tbyte@sztaki.hu>
* @package	StreamOnTheFly
* @version	0.1
*/

class sotf_Permission
{

  var $currentPermissions;

  function sotf_Permission() {
    $this->currentPermissions = $this->getUserPermissions();
  }

  function getUserPermissions($userid='') {
    global $db, $user;
    if(!$userid && is_object($user)) {
      $userid = $user->id;
    }
    $permtable = $db->getAll("SELECT sotf_user_permissions.object_id, sotf_permissions.permission FROM sotf_user_permissions, sotf_permissions WHERE sotf_user_permissions.user_id = '$userid' AND sotf_user_permissions.permission_id = sotf_permissions.id");
    //debug("permtable", $permtable);
    // make an associative array containing the permissions for all objects
    for ($i=0;$i<count($permtable);$i++)
      if (!empty($permtable[$i]["object_id"]))
        $permissions[$permtable[$i]["object_id"]][] = $permtable[$i]["permission"];	// object permission
      else
        $permissions["node"][] = $permtable[$i]["permission"];	// node permission
    debug("current permissions: ", $permissions);
    return $permissions;
  }

	function isEditor() {
    global $repository;
		if(!isset($this->currentPermissions))
      return false;
    while(list($key,$value) = each($this->currentPermissions)) {
      if($repository->getTable($key) == 'sotf_stations' && (in_array('admin', $value) || in_array('create', $value)))
        return true;
    }
    return false;
	}

	function hasPermission($object, $perm) {
		if($this->currentPermissions && $this->currentPermissions[$object])
      return in_array($perm, $this->currentPermissions[$object]) || in_array('admin', $this->currentPermissions[$object]);
    return false;
      //			if ($db->getOne("SELECT sotf_user_permissions.permission_id FROM sotf_user_permissions, sotf_permissions WHERE sotf_user_permissions.user_id = '$user->id' AND sotf_user_permissions.object_id = '$this->id' AND (sotf_permissions.permission = 'admin' OR sotf_permissions.permission = '$perm')"))
	}

	function addPermission($objectId, $userid, $perm) {
    global $db;
		if(!is_numeric($userid) || $userid < 1)
			raiseError("Invalid user id: '$userid'");
		$permission_id = $db->getOne("SELECT id FROM sotf_permissions WHERE permission='$perm'");
		$db->query("INSERT INTO sotf_user_permissions (user_id, object_id, permission_id) VALUES($userid, '$objectId', $permission_id)");
	}

	function delPermission($objectId, $userid, $perm) {
    global $db;
		if(!is_numeric($userid) || $userid < 1)
			raiseError("Invalid user id: '$userid'");
		$permission_id = $db->getOne("SELECT id FROM sotf_permissions WHERE permission='$perm'");
		$db->query("DELETE FROM sotf_user_permissions WHERE user_id = '$userid' and object_id = '$objectId' AND permission_id = $permission_id");
	}

	function listUsersAndPermissions($objectId) {
    global $db;
    // todo:
		return $db->getAll("SELECT sotf_user_permissions.user_id, sotf_permissions.permission FROM sotf_user_permissions, sotf_permissions WHERE sotf_permissions.id = sotf_user_permissions.permission_id");
	}

	function hasNodePermission($perm) {
		if ($this->currentPermissions && $this->currentPermissions['node'] ) {
			if (in_array($perm,$this->currentPermissions["node"]) || in_array('admin',$this->currentPermissions["node"]))
				return true;
    }
		return false;
	}

	function addNodePermission($perm, $userid) {
		global $db;
		$permission_id = $db->getOne("SELECT id FROM sotf_permissions WHERE permission='$perm'");
		$res = $db->query("INSERT INTO sotf_user_permissions (user_id, object_id, permission_id) VALUES($userid, NULL, $permission_id)");
    if(DB::isError($res))
      raiseError($res);
	}

	function delNodePermission($perm, $userid) {
		global $db;
		if(!is_numeric($userid) || $userid < 1)
			raiseError("Invalid user id: '$userid'");
		$permission_id = $db->getOne("SELECT id FROM sotf_permissions WHERE permission='$perm'");
		$res = $db->query("DELETE FROM sotf_user_permissions WHERE user_id = '$userid' AND object_id IS NULL AND permission_id = $permission_id");
    if(DB::isError($res))
      raiseError($res);
	}

	function listNodeUsersWithPerm($perm) {
		global $db, $user;
		$retval = $db->getAll("SELECT u.user_id AS id, p.permission AS perm FROM sotf_user_permissions u, sotf_permissions p WHERE u.object_id IS NULL AND p.id = u.permission_id AND ( p.permission='$perm' OR p.permission='admin')");
    for($i=0;$i<count($retval);$i++) {
      $retval[$i]['name'] = $user->getUserName($retval[$i]['id']);
    }
    debug("listNodeUsersWithPerm", $retval);
    return $retval;
	}

  // not used yet
  /*
	function loadObjectPermissions() {
		global $user, $db;
		if($user && $user->id) {
			$id = $this->id;
			$userid = $user->id;
			$this->permissions = $db->getCol("SELECT permission FROM sotf_permissions s, sotf_user_permissions u WHERE u.object_id = '$id' AND u.user_id = '$userId' AND u.permission_id = s.id");
			if(DB::isError($this->permissions))
				raiseError($this->permissions);
		}
	}
  */


} // end class sotf_Permission



?>