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
    while(list(,$row) = each($permtable)) {
      $permissions[$row["object_id"]][] = $row["permission"];	// object permission
    }
    //debug("current permissions: ", $permissions);
    return $permissions;
  }

	function isEditor() {
    global $repository;
		if(!isset($this->currentPermissions))
      return false;
    reset($this->currentPermissions);
    while(list($key,$value) = each($this->currentPermissions)) {
      $table = $repository->getTable($key);
      if( $table == 'sotf_stations' ) { 
        if( in_array('admin', $value) || in_array('add_prog', $value) ) {
          return true;
        } else 
          debug("nem jo: $key == $table,  $value");
      }
    }
    return false;
	}

	function hasPermission($object, $perm, $userid='') {
    if(empty($userid)) {
      if($this->currentPermissions && $this->currentPermissions[$object])
        return in_array($perm, $this->currentPermissions[$object]) || in_array('admin', $this->currentPermissions[$object]);
      return false;
    } else {
      global $db;
      if ($db->getOne("SELECT u.permission_id FROM sotf_user_permissions u, sotf_permissions p WHERE u.user_id = '$userid' AND u.object_id = '$object' AND p.id=u.permission_id AND (p.permission = 'admin' OR p.permission = '$perm')"))
        return true;
      else
        return false;
    }
	}

	function addPermission($objectId, $userid, $perm) {
    global $db;
		if(!is_numeric($userid) || $userid < 1)
			raiseError("Invalid user id: '$userid'");
    if($perm=='admin') {
      $db->query("DELETE FROM sotf_user_permissions WHERE user_id='$userid' AND object_id='$objectId'");
    }
    //else {
    //  if($this->hasPermission($objectId, 'admin', $userid))
    //    return;
    //}
		$permission_id = $db->getOne("SELECT id FROM sotf_permissions WHERE permission='$perm'");
		$db->query("INSERT INTO sotf_user_permissions (user_id, object_id, permission_id) VALUES($userid, '$objectId', $permission_id)");
	}

	function delPermission($objectId, $userid, $perm = '') {
    global $db;
		if(!is_numeric($userid) || $userid < 1)
			raiseError("Invalid user id: '$userid'");
    if(empty($perm)) {
      // delete all permissions
      $db->query("DELETE FROM sotf_user_permissions WHERE user_id = '$userid' and object_id = '$objectId'");
    } else {
      $permission_id = $db->getOne("SELECT id FROM sotf_permissions WHERE permission='$perm'");
      $db->query("DELETE FROM sotf_user_permissions WHERE user_id = '$userid' and object_id = '$objectId' AND permission_id = $permission_id");
    }
	}

  function getPermissions($objectId, $userid) {
    global $db;
		$retval = $db->getCol("SELECT p.permission FROM sotf_user_permissions u, sotf_permissions p WHERE u.object_id='$objectId' AND u.user_id='$userid' AND p.id = u.permission_id");
    if(DB::isError($retval))
      raiseError($retval);
    return $retval;
  }

  function listUsersWithPermission($objectId, $perm) {
    global $db, $user;
		$retval = $db->getAll("SELECT u.user_id AS id FROM sotf_user_permissions u, sotf_permissions p WHERE u.object_id='$objectId' AND p.id = u.permission_id AND ( p.permission='$perm' OR p.permission='admin')");
    for($i=0;$i<count($retval);$i++) {
      $retval[$i]['name'] = $user->getUserName($retval[$i]['id']);
    }
    return $retval;
  }

	function listUsersAndPermissionsLocalized($objectId) {
    global $db, $user, $page;
		$plist = $db->getAll("SELECT u.user_id AS id, p.permission AS perm FROM sotf_user_permissions u, sotf_permissions p WHERE p.id = u.permission_id AND u.object_id = '$objectId'");
    if(DB::isError($retval))
      raiseError($retval);
    $retval = array();
    while(list(,$perm) = each($plist)) {
      $name = $user->getUserName($perm['id']);
      $retval[$name][] = $page->getlocalized('perm_' . $perm['perm']);
    }
    ksort($retval);
    return $retval;
	}

  /*
	function hasNodePermission($perm) {
		if ($this->currentPermissions && $this->currentPermissions['node'] ) {
			if (in_array($perm,$this->currentPermissions["node"]) || in_array('admin',$this->currentPermissions["node"]))
				return true;
    }
		return false;
	}

	function addNodePermission($userid, $perm) {
		global $db;
		if(!is_numeric($userid) || $userid < 1)
			raiseError("Invalid user id: '$userid'");
    if($perm=='admin') {
      $db->query("DELETE FROM sotf_user_permissions WHERE user_id='$userid' AND object_id IS NULL");
    }
		$permission_id = $db->getOne("SELECT id FROM sotf_permissions WHERE permission='$perm'");
		$res = $db->query("INSERT INTO sotf_user_permissions (user_id, object_id, permission_id) VALUES($userid, NULL, $permission_id)");
    if(DB::isError($res))
      raiseError($res);
	}

	function delNodePermission($userid) {
		global $db;
		if(!is_numeric($userid) || $userid < 1)
			raiseError("Invalid user id: '$userid'");
		$res = $db->query("DELETE FROM sotf_user_permissions WHERE user_id = '$userid' AND object_id IS NULL");
    if(DB::isError($res))
      raiseError($res);
	}

  */
 
  function listStationsForEditor() {
		if(!isset($this->currentPermissions))
      return NULL;  // not logged in yet
    global $db, $user;
    $retval = $db->getAll("SELECT s.name AS name, s.id AS id FROM sotf_stations s, sotf_user_permissions u, sotf_permissions p WHERE u.user_id = '$user->id' AND u.object_id=s.id AND p.id = u.permission_id AND ( p.permission='add_prog' OR p.permission='admin')");
    return $retval;
  }

  /** returns series (id,title) within given station owned/edited by current user */
  function mySeriesData($stationId) {
    global $page, $db, $user;
		if(!$page->loggedIn())
      return NULL;  // not logged in yet
    $stationId = sotf_Utils::magicQuotes($stationId);
    $sql = "SELECT s.id AS id, s.title AS title FROM sotf_series s, sotf_user_permissions u".
    		" WHERE u.user_id = '$user->id'";
//    		" AND u.object_id=s.id";
    if ($stationId) $sql .= " AND s.station_id='$stationId'";
    $sql .= " ORDER BY s.title";
    $sdata = $db->getAll($sql);
    return $sdata;
  }




} // end class sotf_Permission



?>