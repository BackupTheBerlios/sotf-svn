<?php  // -*- tab-width: 3; indent-tabs-mode: 1; -*- 



/*  

 * $Id: topicTree.php 386 2005-06-24 14:33:06Z wreutz $

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

	 $info = $vocabularies->getTopicInfo($open, $lang);

	 $treeId = $info['tree_id'];

  } else {

	 $treeId = $vocabularies->getDefaultTreeId();

  }

}

debug("treeid", $treeId);

$rootId = $vocabularies->getTopicTreeRoot($treeId);



if($prgid) { 

  $addMode = 1;

  $smarty->assign("OPENER_URL", "editMeta.php?id=$prgid");

}

$smarty->assign('ADD_MODE', $addMode);



// list all topic trees

$smarty->assign('TREES', $vocabularies->listTopicTrees($lang));



// TODO; use user's language

$info = $vocabularies->getTopicTreeInfo($treeId, $lang);

debug("INFO", $info);

$treeLang = $lang;

if(strpos($info['languages'], $lang) === FALSE)

	  $treeLang = 'eng'; // fall back to English



$result = $vocabularies->getTree($treeId, $treeLang, true);

$smarty->assign('TREE_ID', $treeId);

$smarty->assign("TREE", $result);



$smarty->assign("prgid", sotf_Utils::getParameter('prgid'));



$page->sendPopup("topicTree.htm");



?>
