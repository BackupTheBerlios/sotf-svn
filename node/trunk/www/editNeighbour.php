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
$page->errorURL = "editNeighbour.php";

checkPerm('node', "change");

$nid = sotf_Utils::getParameter('nodeid');
$nei = sotf_Neighbour::getById($nid);
if(!$nei)
	  raiseError("No such node: $nid");

// save changes
if(sotf_Utils::getParameter('save')) {
		$nei->set('use_for_outgoing',(sotf_Utils::getParameter('use_out') ? 't' : 'f' ));
		$nei->set('accept_incoming', (sotf_Utils::getParameter('use_in') ? 't' : 'f' ));
		$nei->update();
		$page->redirect("closeAndRefresh.php?anchor=network");
		exit;
}

// generate output

$node = sotf_Node::getNodeById($nid);

$smarty->assign('NODE',$node->getAll());
$smarty->assign('NEI', $nei->getAll());

$page->sendPopup();