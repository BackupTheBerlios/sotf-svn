<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id: sotf_User.class.php 548 2006-04-03 16:21:28Z buddhafly $

/** 
* This is a class for basic handling of users. Preferences and
* playlists are handled in separate classes.
*
* @author Andras Micsik SZTAKI DSD micsik@sztaki.hu, Martin Schmidt ptmschmidt@fh-stpoelten.ac.at
*/

class sotf_Group extends sotf_Object {

  function sotf_Group($id='', $data='') {
          $this->sotf_Object('sotf_groups', $id, $data);
  }

  function countAll() {
    global $db;
    $sql = "SELECT count(*) FROM sotf_groups g";
    $res = $db->getOne($sql);
    if(DB::isError($res))
            raiseError($res);
    return $res;
  }

  /** returns a list of all such objects: can be slow!!
   * @method static listAll
   */
  function listAll($withCounts=1) {
    global $db;
    if($withCounts)
      $sql = "SELECT g.*, count(u.id) as count FROM sotf_groups g LEFT JOIN sotf_user_groups u ON g.id=u.group_id GROUP BY g.name, g.id, g.comments, g.price ORDER BY g.name";
    else
      $sql = "SELECT * FROM sotf_groups g";
    $res = $db->getAll($sql);
    if(DB::isError($res))
            raiseError($res);
    return $res;
  }


  /** 
   * @method static getById
   */
  function getById($gid) {
          global $db;
          $gid = sotf_Utils::magicQuotes($gid);
          $id = $db->getOne("SELECT id FROM sotf_groups WHERE id = '$gid'");
          if(DB::isError($id))
                  raiseError($id);
          if($id)
                  return new sotf_Group($id);
          else
                  return NULL;
  }

  function getGroupName($gid) {
          global $db;
			 $gid = getGroupId($gid);
          $gid = sotf_Utils::magicQuotes($gid);
          $id = $db->getOne("SELECT name FROM sotf_groups WHERE id = '$gid'");
          if(DB::isError($id))
                  raiseError($id);
          return $id;
  }

  /** 
   * @method static getByName
   */
  function getByName($name) {
          global $db;
          $name = sotf_Utils::magicQuotes($name);
          $id = $db->getOne("SELECT id FROM sotf_groups WHERE name = '$name'");
          if(DB::isError($id))
                  raiseError($id);
          if($id)
                  return new sotf_Group($id);
          else
                  return NULL;
  }

  function getGroupNames($uid) {
    global $db;
    $uid = sotf_Utils::magicQuotes($uid);
    $list = $db->getCol("SELECT g.name FROM sotf_groups g, sotf_user_groups u WHERE g.id=u.group_id AND u.user_id='$uid'");
	 natcasesort($list);
    return $list;
  }

  function setGroup($uid, $gid, $member, $rid='') {
    if($rid) {
      if(!$member) {
        $o = new sotf_Object('sotf_user_groups', $rid);
        $o->delete();
      }
      return;
    }
    $o = new sotf_Object('sotf_user_groups');
    $o->set('user_id', $uid);
    $o->set('group_id', $gid);
    $o->find();
    debug("EXISTS", $o->exists());
    debug("MEM", $member);
    if($member) {
      if(!$o->exists())
        $o->create();
    } else {
      if($o->exists())
        $o->delete();
    }
  }

  function listObjectsOfGroup() {
	 global $repository, $permissions;
	 $retval = $permissions->listObjectsInGroup($this->id);
	 return $retval;
  }

  /** Static */
  function listGroupIdsOfObject($id, $perm='listen') {
    global $repository, $permissions;
	 if(!$id)
		return array();
	 $obj = & $repository->getObject($id);
	 $perms = $permissions->listGroupsAndPermissions($id);
	 $groups = array();
	 //debug("PERMS", $perms);
	 foreach($perms as $row) {
		if($row['perm']==$perm) {
		  $groups[] = substr($row['id'], 1);
		}
	 }
	 $fields = $obj->getAll();
	 // propagate query upwards
	 if($fields['series_id']) {
		$groups = array_merge($groups, sotf_Group::listGroupIdsOfObject($fields['series_id'], $perm));
	 } elseif($fields['station_id']) {
		$groups = array_merge($groups, sotf_Group::listGroupIdsOfObject($fields['station_id'], $perm));
	 }
	 $groups = array_unique($groups);
	 //debug("GROUP IDS", $groups);
	 return $groups;
  }

  function listGroupsOfObject($id, $perm='listen') {
	 $groups = sotf_Group::listGroupIdsOfObject($id, $perm);
	 $retval = array();
	 foreach($groups as $gid) {
		$group = sotf_Group::getById($gid);
		$retval[$group->get('name')] = $group->getAll();
	 }
	 asort($retval);
	 //debug("GROUPS", $retval);
    return $retval;
  }

  function listGroupsOfUser($uid) {
    global $db;
	 if(!$uid)
		return array();
    $uid = sotf_Utils::magicQuotes($uid);
    $sql = "SELECT group_id, id FROM sotf_user_groups WHERE user_id='$uid'";
    $res = $db->getAssoc($sql);
    if(DB::isError($res))
            raiseError($res);
    return $res;
  }

  /** Search for groups. */
  function findGroups($pattern, $prefix = false) {
	 global $db;
    if($prefix) {
      $query = sprintf("SELECT id AS userid, name FROM sotf_groups WHERE name ~* '^%s' ORDER BY name",
		       $pattern);
      $res = $db->getAssoc($query);
    } else {
      $query = sprintf("SELECT id AS userid, name FROM sotf_groups WHERE name ~* '%s' ORDER BY name",
		       $pattern);
      $res = $db->getAssoc($query);
    }
	 if(DB::isError($res))
		raiseError($res);
	 return $res;
  }
           
}

?>