<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

/**
 * This is a class for handling permossions.
 *
 * @author	Tamas Kezdi SZTAKI DSD <tbyte@sztaki.hu>
 * @package	StreamOnTheFly
 * @version	0.1
 */


class sotf_Permission {

  var $debug = false;
  var $currentPermissions;
  var $isEditor = false;

  function sotf_Permission() {
    $this->currentPermissions = $this->getUserPermissions();
  }

  function getUserPermissions($userid='') {
    global $db, $user, $repository;
    if(!$userid && is_object($user)) {
      $userid = $user->id;
    }
    if ($userid) {
		$permtable = $db->getAll("SELECT sotf_user_permissions.object_id, sotf_permissions.permission FROM sotf_user_permissions, sotf_permissions WHERE sotf_user_permissions.user_id = '$userid' AND sotf_user_permissions.permission_id = sotf_permissions.id");
		//debug("permtable", $permtable);
		// make an associative array containing the permissions for all objects
		while(list(,$row) = each($permtable)) {
		  $permissions[$row["object_id"]][] = $row["permission"];	// object permission
      }
    }
    if($this->debug) {
      error_log("current permissions",0);
      if(count($permissions) > 0) {
        foreach($permissions as $key => $value) {
          error_log("PERMISSION: $key = " . join(' ',$value),0);
        }
      }
    }
    return $permissions;
  }


	function hasPermission($object, $perm, $userid='') {
    if(empty($userid)) {
		$retval = false;
      if($this->currentPermissions && $this->currentPermissions[$object])
        $retval = in_array($perm, $this->currentPermissions[$object]) || in_array('admin', $this->currentPermissions[$object]);
		if($this->debug)
		  error_log("checking for permission " . $perm . " on " . $object . ": " . $retval, 0);
      return $retval;
    } else {
      global $db;
		$retval = false;
      if ($db->getOne("SELECT u.permission_id FROM sotf_user_permissions u, sotf_permissions p WHERE u.user_id = '$userid' AND u.object_id = '$object' AND p.id=u.permission_id AND (p.permission = 'admin' OR p.permission = '$perm')"))
        $retval = true;
		if($this->debug)
		  error_log("checking for user " . $userid . " permission " . $perm . " on " . $object . ": " . $retval, 0);
		return $retval;
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
    global $db;
		$retval = $db->getAll("SELECT u.user_id AS id FROM sotf_user_permissions u, sotf_permissions p WHERE u.object_id='$objectId' AND p.id = u.permission_id AND ( p.permission='$perm' OR p.permission='admin')");
    for($i=0;$i<count($retval);$i++) {
      $retval[$i]['name'] = sotf_User::getUserName($retval[$i]['id']);
    }
    return $retval;
  }

	function listUsersAndPermissions($objectId) {
	  global $db;
	  $retval = $db->getAll("SELECT u.user_id AS id, p.permission AS perm FROM sotf_user_permissions u, sotf_permissions p WHERE p.id = u.permission_id AND u.object_id = '$objectId'");
	  if(DB::isError($retval))
		 raiseError($retval);
	  for($i=0; $i<count($retval); $i++) {
		 $retval[$i]['name'] =  sotf_User::getUserName($retval[$i]['id']);
	  }
	  return $retval;
	}

	/** private */
	function sortUsersByName($a, $b) {
	  return strcasecmp($a['name'], $b['name']);
	}

	function listUsersAndPermissionsLocalized($objectId) {
	  global $db, $page;
	  $plist = $db->getAll("SELECT u.user_id AS id, p.permission AS perm FROM sotf_user_permissions u, sotf_permissions p WHERE p.id = u.permission_id AND u.object_id = '$objectId'");
	  if(DB::isError($plist))
		 raiseError($plist);
	  $retval = array();
	  while(list(,$perm) = each($plist)) {
		 $id = $perm['id'];
		 if(!$retval[$id]['name'])
			$retval[$id]['name'] = sotf_User::getUserName($id);
		 $retval[$id]['permissions'][] = $page->getlocalized('perm_' . $perm['perm']);
	  }
	  uasort($retval, array('sotf_Permission', 'sortUsersByName'));
	  return $retval;
	}

	function isEditor() {
	  global $repository;
	  if(empty($this->currentPermissions))
		 return false;
	  
	  reset($this->currentPermissions);
	  
	  while(list($key,$value) = each($this->currentPermissions)) {
		 $table = $repository->getTable($key);
		 if( $table == 'sotf_stations' || $table == 'sotf_series') { 
			if( in_array('admin', $value) || in_array('create', $value) ) {
			  return true;
			} else { 
			  debug("nem jo: $key == $table,  $value");
			}
		 }
	  }
	  return false;
	}
	
	function listStationsForEditor($withSeries = true) {
	  if(!isset($this->currentPermissions))
		 return NULL;  // not logged in yet
	  global $db, $user;
	  $retval1 = $db->getAll("SELECT 'station' AS type, s.name AS name, s.id AS id FROM sotf_stations s, sotf_user_permissions u, sotf_permissions p WHERE u.user_id = '$user->id' AND u.object_id=s.id AND p.id = u.permission_id AND ( p.permission='create' OR p.permission='admin')");
	  if(!$withSeries)
		 return $retval1;
	  $retval2 = $db->getAll("SELECT 'series' AS type, s.name AS name, s.id AS id, s.station_id FROM sotf_series s, sotf_user_permissions u, sotf_permissions p WHERE u.user_id = '$user->id' AND u.object_id=s.id AND p.id = u.permission_id AND ( p.permission='create' OR p.permission='admin')");
    return array_merge($retval1, $retval2);
  }

  /** returns series (id,namex) within given station owned/edited by current user */
  function mySeriesData($stationId) {
    global $page, $db, $user;
		if(!$page->loggedIn())
      return NULL;  // not logged in yet
    $stationId = sotf_Utils::magicQuotes($stationId);
    $sql = "SELECT s.id AS id, s.name AS name FROM sotf_series s, sotf_user_permissions u".
      " WHERE u.user_id = '$user->id' AND u.object_id=s.id";
    if ($stationId) $sql .= " AND s.station_id='$stationId'";
    $sql .= " ORDER BY s.name";
    $sdata = $db->getAll($sql);
    return $sdata;
  }
} // end class sotf_Permission

?>
