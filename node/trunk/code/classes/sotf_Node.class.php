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
		global $db, $user;
    $userid = $user->id;
		if ($db->getOne("SELECT sotf_user_permissions.permission_id FROM sotf_user_permissions, sotf_permissions WHERE sotf_user_permissions.user_id = '$userid' AND sotf_user_permissions.object_id IS NULL AND (sotf_permissions.permission = 'admin' OR sotf_permissions.permission = '$perm')"))
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

}

?>
