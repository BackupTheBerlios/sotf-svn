<?php

require("init.inc.php");

$smarty->assign('PAGETITLE',$page->getlocalized('AdminPage'));

$page->forceLogin();
$page->popup = true;
$page->errorURL = "createNeighbour.php";
if (!hasPerm('node', "change")) {
  raiseError("You have no permission to change node settings!");
}

//
$createNew = sotf_Utils::getParameter('create_new_node');
if($createNew) {
  $node = new sotf_Node;
  $nid = sotf_Utils::getParameter('node_id');
  $name = sotf_Utils::getParameter('name');
  $url = sotf_Utils::getParameter('url');
  // todo: check exists?
  $node->nodeId = $nid;
  $node->set('node_id', $nid);
  $node->set('name', $name);
  $node->set('url', $url);
  $node->create();
  
  $neighbor = new sotf_Neighbour();
  $neighbor->set('node_id', $nid);
  $neighbor->set('use_for_outgoing', 'f');
  $neighbor->set('accept_incoming', 't');
  $neighbor->create();
 
  $page->redirect("closeAndRefresh?anchor=network");
  exit;
}

// generate output

$nodes = sotf_Node::listAll();
$nodeData = array();
while(list(,$node)= each($nodes)) {
  if(!sotf_Neighbour::isNeighbour($node->get('node_id')) && $nodeId != $node->get('node_id')) {
    $nodeData[] = $node->getAll();
  }
}

$smarty->assign('NODES',$nodeData);


$page->sendPopup();