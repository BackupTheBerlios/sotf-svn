<?php  // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

$topic_id = sotf_Utils::getParameter('topic_id');
$find = sotf_Utils::getParameter('find');
$prgid = sotf_Utils::getParameter('prgid');
$open = sotf_Utils::getParameter('open');

$treeId = sotf_Utils::getParameter('tree');
if(!$treeId) {
  if($open) {
	 $info = $repository->getTopicInfo($open, $lang);
	 $treeId = $info['tree_id'];
  } else {
	 $treeId = $repository->getDefaultTreeId();
  }
}
debug("treeid", $treeId);
$rootId = $repository->getTopicTreeRoot($treeId);

if($prgid) { 
  $addMode = 1;
  $smarty->assign("OPENER_URL", "editMeta.php?id=$prgid");
}
$smarty->assign('ADD_MODE', $addMode);

// list all topic trees
$smarty->assign('TREES', $repository->listTopicTrees($lang));

$result = $repository->getTree($treeId, 'en', true);
$smarty->assign('TREE_ID', $treeId);
$smarty->assign("TREE", $result);

$smarty->assign("prgid", sotf_Utils::getParameter('prgid'));

//$page->sendPopup();
$page->send("topicTree.htm");
?>