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

//
$createNew = sotf_Utils::getParameter('create_new_node');
if($createNew) {
  if(!$url) {
    $page->addStatusMsg('no_url_given');
  } elseif(sotf_Node::getNodeById($nid)) {
    $page->addStatusMsg('node_id_occupied');
  } else {
    $neighbor = new sotf_Neighbour();
    $neighbor->set('node_id', $nid);
    $neighbor->set('use_for_outgoing', 'f');
    $neighbor->set('accept_incoming', 't');
    $neighbor->set('pending_url', $url);
    $neighbor->create();
    $page->redirect("closeAndRefresh.php?anchor=network");
    exit;
  }
  $page->redirect("createNeighbour.php?node_id=$nid&url=" . urlencode($url) . "#network");
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
 $smarty->assign('NID', $nid);
 $smarty->assign('URL', $url);


$page->sendPopup();