<?php

require("init.inc.php");

$smarty->assign('PAGETITLE',$page->getlocalized('AdminPage'));

$page->forceLogin();

$page->errorURL = "admin.php";

if (!hasPerm('node', "change")) {
  raiseError("You have no permission to change node settings!");
}

// update CVS
if(sotf_Utils::getParameter('updatecvs')) {
  chdir($basedir);
  header("Content-type: text/plain\n");
  system('cvs update');
  //$page->redirect("admin.php");
  exit;
}

// recompile Smarty templates
if(sotf_Utils::getParameter('retemplate')) {
  $smarty->clear_compiled_tpl();
  $page->redirect("admin.php");
  exit;  
}

// update topic counts
if(sotf_Utils::getParameter('updatetopics')) {
  $repository->updateTopicCounts();
  $page->redirect("admin.php");
  exit;  
}

// save general data
$save = sotf_Utils::getParameter('save_debug');
if($save) {
  $sotfVars->set('debug', sotf_Utils::getParameter('debug'));
  $sotfVars->set('debug_sql', sotf_Utils::getParameter('debug_sql'));
  $sotfVars->set('debug_smarty', sotf_Utils::getParameter('debug_smarty'));
  $sotfVars->set('smarty_compile_check', sotf_Utils::getParameter('smarty_compile_check'));
  $page->redirect("admin.php");
  exit;
}

// save network data
$save = sotf_Utils::getParameter('save');
if($save) {
  $desc = sotf_Utils::getParameter('desc');
  $localNode = sotf_Node::getLocalNode();
  $localNode->set('description', $desc);
  $localNode->update();
  $page->redirect("admin.php#network");
  exit;
}

// sync
$sync = sotf_Utils::getParameter('sync');
if($sync) {
  // this can be long duty!
  set_time_limit(18000);
  // get sync stamp and increment it
  $syncStamp = $sotfVars->get('sync_stamp', 0);
  $syncStamp++;
  $sotfVars->set('sync_stamp', $syncStamp);
  // get neighbour object
  $nid = sotf_Utils::getParameter('nodeid');
  $neighbour = sotf_Neighbour::getById($nid);
  // sync
  $neighbour->sync(true);
  $page->redirect("admin.php#network");
}

// delete neighbour
$deln = sotf_Utils::getParameter('delneighbour');
debug("deln", $deln);
if($deln) {
  debug("deln", "!!");
  $nid = sotf_Utils::getParameter('nodeid');
  $neighbour = sotf_Neighbour::getById($nid);
  $neighbour->delete();
  $page->redirect("admin.php#network");
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

$localNode = sotf_Node::getLocalNode();
if(!$localNode) {
  $localNode = new sotf_Node();
  $localNode->set('node_id', $nodeId);
  $localNode->set('name', $nodeName);
  $localNode->set('url', $rootdir);
  $localNode->create();
}

$smarty->assign("LOCAL_NODE", $localNode->getAll());

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

$smarty->assign("VARS", $sotfVars->getAll());

$page->send();

?>
