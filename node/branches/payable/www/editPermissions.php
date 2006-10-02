<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

$page->popup = true;
$page->forceLogin();

$context = sotf_Utils::getParameter('context');
$objectname = sotf_Utils::getParameter('objectname');
$objectid = sotf_Utils::getParameter('objectid');
$userid = sotf_Utils::getParameter('userid');
$save = sotf_Utils::getParameter('save');
$pattern = sotf_Utils::getParameter('pattern');
$prefix = sotf_Utils::getParameter('prefix');
$new = sotf_Utils::getParameter('new');

if($new) {
  // restart user search
} elseif($userid) {
  if($userid{0} == 'g') {
	 $gid = substr($userid, 1);
	 $group = sotf_Group::getById($gid);
	 $users[$userid] = $group->get('name');
	 $smarty->assign("USERS", $users);
  } else {
	 $username = sotf_User::getUsername($userid);
	 $users[$userid] = $username;
	 $smarty->assign("USERS", $users);
  }
} elseif($pattern) {
  $smarty->assign("PATTERN", $pattern);
  $users = sotf_User::findUsers($pattern, $prefix);
  debug("USERS", $users);
  $groups = sotf_Group::findGroups($pattern, $prefix);
  debug("GROUPS", $groups);
  if(count($users) + count($groups) > 50) {
	 $smarty->assign("TOO_MANY_MATCHES", count($users)+count($groups));
  } elseif(empty($users) and empty($groups)) {
	 $smarty->assign("NO_MATCHES", 1);
  } else {
	 $smarty->assign("USERS", $users);
	 $smarty->assign("GROUPS", $groups);
  }
}

checkPerm($objectid, "authorize");

if($save) {
  if($userid == $user->id) {
	 // trying to change permissions for self
	 //if(!hasPerm($objectid, 'admin'))
	 //raiseError("self_perm_change_not_allowed");
	 $page->addStatusMsg("self_perm_change_not_allowed");
  } else {
	 $userPerms = $permissions->getPermissions($objectid, $userid);
	 debug("userPerms", $userPerms);
	 if(sotf_Utils::getParameter('perm_admin')) {
		if(!in_array('admin', $userPerms))
		  $permissions->addPermission($objectid, $userid, 'admin');
	 } else {
		if(in_array('admin', $userPerms))
		  $permissions->delPermission($objectid, $userid, 'admin');
		$perms['listen'] = sotf_Utils::getParameter('perm_listen');
		$perms['create'] = sotf_Utils::getParameter('perm_create');
		$perms['change'] = sotf_Utils::getParameter('perm_change');
		//$perms['add_prog'] = sotf_Utils::getParameter('perm_add_prog');
		$perms['delete'] = sotf_Utils::getParameter('perm_delete');
		$perms['authorize'] = sotf_Utils::getParameter('perm_authorize');
		while(list($perm, $hasP) = each($perms)) {
		  if($hasP && !in_array($perm, $userPerms))
			 $permissions->addPermission($objectid, $userid, $perm);
		  elseif(!$hasP && in_array($perm, $userPerms))
			 $permissions->delPermission($objectid, $userid, $perm);
		}
	 }
	 $page->redirect('closeAndRefresh.php?anchor=perms');
  }
}

$smarty->assign('CONTEXT', $context);
//$smarty->assign('ADMIN_EXPL', $page->getlocalized($context . '_admin_expl'));
//$smarty->assign('CHANGE_EXPL', $page->getlocalized($context . '_change_expl'));
//$smarty->assign('CREATE_EXPL', $page->getlocalized($context . '_create_expl'));
//$smarty->assign('DELETE_EXPL', $page->getlocalized($context . '_delete_expl'));
//$smarty->assign('AUTHORIZE_EXPL', $page->getlocalized($context . '_authorize_expl'));

$smarty->assign('OBJECT_NAME', $objectname);
$smarty->assign('OBJECT_ID', $objectid);
//$smarty->assign('USER_NAME', $username);
$smarty->assign('USER_ID', $userid);

if($userid)
	  $smarty->assign('PERMISSIONS', $permissions->getPermissions($objectid, $userid));

$page->sendPopup();

?>