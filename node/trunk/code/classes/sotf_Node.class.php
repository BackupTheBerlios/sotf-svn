<?php
// -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

class sotf_Node extends sotf_NodeObject {

	var $tablename = 'sotf_nodes';

	function sotf_Node($id='', $data='') {
		return new sotfNodeObject('sotf_nodes', $id, $data);
	}

	/** 
	 * @method static getNodeById
	 */
	function getNodeById($nodeId) {
		global $db;
		$id = $db->getOne("SELECT id FROM sotf_nodes WHERE node_id = '$nodeId'");
		if(DB::isError($id))
			raiseError($id);
		return new sotf_Node($id);
	}

	/** 
	 * @method static getLocalNode
	 */
	function getLocalNode() {
		global $db, $nodeId;
		return sotf_Node::getNodeById($nodeId);
	}

	/** returns a list of all such objects: can be slow!!
	 * @method static listAll
	 */
	function listAll() {
		global $db;
		$sql = "SELECT * FROM sotf_nodes ORDER BY name";
		$res = $db->getAll($sql);
		if(DB::isError($res))
			raiseError($res);
		foreach($res as $st) {
			$slist[] = new sotf_Node($st['id'], $st);
		}
		return $slist;
	}

	/** 
	 * @method static countAll
	 */
	function countAll() {
		global $db;
		return $db->getOne("SELECT count(*) FROM sotf_nodes WHERE up='t'");
	}


	function hasPermission($perm) {
		global $user;
		//if ($db->getOne("SELECT sotf_user_permissions.permission_id FROM sotf_user_permissions, sotf_permissions WHERE sotf_user_permissions.user_id = '$userid' AND sotf_user_permissions.object_id IS NULL AND (sotf_permissions.permission = 'admin' OR sotf_permissions.permission = '$perm')"))
		if ($user->exist)
			if (in_array($perm,$user->permissions["node"]) || in_array('admin',$user->permissions["node"]))
				return true;
		return false;
	}

	function addPermission($perm, $userid) {
		global $db;
		$permission_id = $db->getOne("SELECT id FROM sotf_permissions WHERE permission='$perm'");
		$db->query("INSERT INTO sotf_user_permissions (user_id, object_id, permission_id) VALUES($userid, NULL, $permission_id)");
	}

	function delPermission($perm, $userid) {
		global $db;
		$permission_id = $db->getOne("SELECT id FROM sotf_permissions WHERE permission='$perm'");
		$db->query("DELETE FROM sotf_user_permissions WHERE user_id = $userid and object_id IS NULL AND permission_id = $permission_id");
	}

	function listUsersAndPermissions() {
		global $db;
		return $db->getAll("SELECT sotf_user_permissions.user_id, sotf_permissions.permission FROM sotf_user_permissions, sotf_permissions WHERE sotf_permissions.id = sotf_user_permissions.permission_id");
	}

	/**
	* Adds a new administrator to the node.
	*
	* @param	string	$username	Username
	* @return	boolean	Returns true if succeeded
	* @use	$db
	*/
	function addAdmin($username)
	{
		global $db;

		$user_id = sotf_User::getUserid($username);
		if ($user_id !== false)
		{
			$sm = $db->getCol("SELECT user_id FROM sotf_user_permissions WHERE user_id='$user_id' AND object_id IS NULL");
			if (count($sm) == 0)
			{
				$permission_id = $db->getOne("SELECT id FROM sotf_permissions WHERE permission = 'admin'");	// get admin permission id
				$db->query("INSERT INTO sotf_user_permissions (user_id, object_id, permission_id) VALUES('$user_id', NULL, $permission_id)");
				return true;
			}
		}
		return false;
	} // end func addAdmin

	/**
	* Removes an administrator from the node.
	*
	* @param	string	$username	Username
	* @return	boolean	Returns true if succeeded
	* @use	$db
	*/
	function removeAdmin($username)
	{
		global $db;

		$user_id = sotf_User::getUserid($username);
		if ($user_id !== false)
		{
			$permission_id = $db->getOne("SELECT id FROM sotf_permissions WHERE permission = 'admin'");	// get admin permission id
			$db->query("DELETE FROM sotf_user_permissions WHERE user_id = $user_id AND object_id IS NULL");
			return true;
		}
		return false;
	} // end func removeAdmin
}

?>
