<?php

require("init.inc.php");

$page->forceLogin();

$context = sotf_Utils::getParameter('context');
$objectname = sotf_Utils::getParameter('objectname');
$objectid = sotf_Utils::getParameter('objectid');
$username = sotf_Utils::getParameter('username');
$save = sotf_Utils::getParameter('save');
$userid = $user->getUserid($username);

if($save) {
  $userPerms = $permissions->getPermissions($objectid, $userid);
  debug("userPerms", $userPerms);
  if(sotf_Utils::getParameter('perm_admin')) {
    if(!in_array('admin', $userPerms))
      $permissions->addPermission($objectid, $userid, 'admin');
  } else {
    if(in_array('admin', $userPerms))
      $permissions->delPermission($objectid, $userid, 'admin');
    $perms['create'] = sotf_Utils::getParameter('perm_create');
    $perms['change'] = sotf_Utils::getParameter('perm_change');
    $perms['add_prog'] = sotf_Utils::getParameter('perm_add_prog');
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



if(empty($userid) || !is_numeric($userid)) {
  raiseError("Invalid username: $username");
}

if (!hasPerm($objectid, "change")) {
  raiseError("You have no permission to change user permissions!");
}

$smarty->assign('CONTEXT', $context);
$smarty->assign('OBJECT_NAME', $objectname);
$smarty->assign('OBJECT_ID', $objectid);
$smarty->assign('USER_NAME', $username);
$smarty->assign('USER_ID', $userid);

$smarty->assign('PERMISSIONS', $permissions->getPermissions($objectid, $userid));

$page->sendPopup();

?>