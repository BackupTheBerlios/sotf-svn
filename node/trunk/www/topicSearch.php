<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

$treeId = sotf_Utils::getParameter('tree');
$ID = sotf_Utils::getParameter('ID');
//$NAME = sotf_Utils::getParameter('NAME');

if(!$ID) {
  $ID = $repository->getTopicTreeRoot($treeId);
}

if($ID) {
  $info = $repository->getTopicInfo($ID, $lang);
  $smarty->assign("TOPIC", $info);
  $treeId = $info['tree_id'];
  $NAME = $info['name'];
}

// collect subtopics
$subtopics = $repository->getSubTopics($ID, $lang);
$smarty->assign('SUBTOPICS', $subtopics);

// collect supertopics
$super = $repository->getSuperTopic($ID, $lang);
while($super) {
  $supertopics[] = $super;
  $super = $repository->getSuperTopic($super['id'], $lang);
}
if($supertopics) {
  $smarty->assign('SUPERTOPICS', array_reverse($supertopics));
}

if(!$supertopics) {
  // collect info on topic tree
  $smarty->assign('TREE', $repository->getTopicTreeInfo($treeId, $lang));
  // list all topic trees
  $smarty->assign('TREES', $repository->listTopicTrees($lang));
}

$max = $repository->countProgsForTopic($ID);

if($max > 0) {
  $limit = $page->splitList($max, "?ID=$ID");

  $result = $repository->getProgsForTopic($ID, $limit["from"], $limit["maxresults"]);

  // cache icons for results
  for($i=0; $i<count($result); $i++) {
	 $result[$i]['icon'] = sotf_Blob::cacheIcon($result[$i]['id']);
  }
}

$smarty->assign("ID", $ID);						//topic id
$smarty->assign("NAME", $NAME);						//topic name
$smarty->assign("query", $query);					//query
$smarty->assign("result", $result);					//result array

$page->send("main_frame_left.htm");
?>