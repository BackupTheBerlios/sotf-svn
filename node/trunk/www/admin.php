<?php

require("init.inc.php");

$smarty->assign('PAGETITLE',$page->getlocalized('AdminPage'));

$page->forceLogin();

$page->errorURL = "admin.php";

if (!hasPerm('node', "change")) {
  raiseError("You have no permission to change node settings!");
}

/*
// save general data
$save = sotf_Utils::getParameter('save');
if($save) {
  $desc = sotf_Utils::getParameter('desc');
  $st->set('description', $desc);
  $st->update();
  $page->redirect("editStation.php?stationid=$stationid");
  exit;
}
*/

// sync
$sync = sotf_Utils::getParameter('sync');
if($sync) {
  $nodeId = sotf_Utils::getParameter('nodeid');
  $neighbour = sotf_Neighbour::getById($nodeId);
  $neighbour->sync();
  $page->redirect("admin.php#network");
  exit;
}

// manage permissions
$delperm = sotf_Utils::getParameter('delperm');
if($delperm) {
  $username = sotf_Utils::getParameter('username');
  $userid = $user->getUserid($username);
  if(empty($userid) || !is_numeric($userid)) {
    raiseError("Invalid username: $username");
  }
  $permissions->delPermission('node', $userid);
  $msg = $page->getlocalizedWithParams("deleted_permissions_for", $username);
  $page->addStatusMsg($msg, false);
  $page->redirect("admin.php");
  exit;
}

// generate output

// nodes
//$nodes = sotf_Node::countAll();
//$smarty->assign('NODES',$nodeData);

// neighbours
$neighbours = sotf_Neighbour::listAll();
while(list(,$nei)= each($neighbours)) {
  $node = sotf_Node::getNodeById($nei->get('node_id'));
  $data = $nei->getAll();
  if($node)
    $data['node'] = $node->getAll();
  $neighbourData[] = $data;
}
$smarty->assign('NEIGHBOURS',$neighbourData);

// user permissions: editors and managers
$smarty->assign('PERMISSIONS', $permissions->listUsersAndPermissionsLocalized('node'));

$page->send();

?>
