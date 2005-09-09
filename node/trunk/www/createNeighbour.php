<?php  // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

$smarty->assign('PAGETITLE',$page->getlocalized('AdminPage'));

$page->forceLogin();
$page->popup = true;
$page->errorURL = "createNeighbour.php";

checkPerm('node', "change");

$url = sotf_Utils::getParameter('url');
$nid = sotf_Utils::getParameter('node_id');

if($nid && !$url) {
  $node = sotf_Node::getNodeById($nid);
  $url = $node->get('url');
}

//
$createNew = sotf_Utils::getParameter('create_new_node');
if($createNew) {
  if(!$url) {
    $page->addStatusMsg('no_url_given');
  } else {
	 // TODO: test URL correctness
	 $node = sotf_Node::getNodeById($nid);
	 if($node && $node->get('url') != $url) {
		$page->addStatusMsg('node_id_occupied');
	 } else {
		if(!sotf_NodeObject::hasObjects($nid)) {
		  // this node is new in the network,
		  // all data has to be sent
		  sotf_NodeObject::newNodeInNetwork($nid);
		}
		$neighbor = new sotf_Neighbour();
		$neighbor->set('node_id', $nid);
		$neighbor->set('use_for_outgoing', 't');
		$neighbor->set('accept_incoming', 't');
		$neighbor->set('pending_url', $url);
		$neighbor->set('errors', '0');
		$neighbor->set('success', '0');
		$neighbor->create();
		$page->redirect("closeAndRefresh.php?anchor=network");
		exit;
	 }
  }
  $page->redirect("createNeighbour.php?node_id=$nid&url=" . urlencode($url) . "#network");
  exit;
}



// generate output

$nodes = sotf_Node::listAll();
$nodeData = array();
while(list(,$node)= each($nodes)) {
  if(!sotf_Neighbour::isNeighbour($node->get('node_id')) && $config['nodeId'] != $node->get('node_id')) {
    $nodeData[] = $node->getAll();
  }
}

 $smarty->assign('NODES',$nodeData);
 $smarty->assign('NID', $nid);
 $smarty->assign('URL', $url);


$page->sendPopup();