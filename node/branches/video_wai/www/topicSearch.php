<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 



/*  

 * $Id: topicSearch.php 386 2005-06-24 14:33:06Z wreutz $

 * Created for the StreamOnTheFly project (IST-2001-32226)

 * Authors: András Micsik, Máté Pataki, Tamás Déri 

 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu

 */



require("init.inc.php");



$treeId = sotf_Utils::getParameter('tree');

$ID = sotf_Utils::getParameter('ID');

//$NAME = sotf_Utils::getParameter('NAME');



if(!$ID) {

  $ID = $vocabularies->getTopicTreeRoot($treeId);

}



if($ID) {

  $info = $vocabularies->getTopicInfo($ID, $lang);

  $smarty->assign("TOPIC", $info);

  $treeId = $info['tree_id'];

  $NAME = $info['name'];

}

// collects entire tree, added by mh060925
$smarty->assign("ALLTOPICS", $vocabularies->getTree($treeId,$lang,true));

// collect subtopics

$subtopics = $vocabularies->getSubTopics($ID, $lang);

$smarty->assign('SUBTOPICS', $subtopics);



// collect supertopics

$super = $vocabularies->getSuperTopic($ID, $lang);

while($super) {

  $supertopics[] = $super;

  $super = $vocabularies->getSuperTopic($super['id'], $lang);

}

if($supertopics) {

  $smarty->assign('SUPERTOPICS', array_reverse($supertopics));

}



if(!$supertopics) {

  // collect info on topic tree

  $smarty->assign('TREE', $vocabularies->getTopicTreeInfo($treeId, $lang));

  // list all topic trees

  $smarty->assign('TREES', $vocabularies->listTopicTrees($lang));

}



$max = $vocabularies->countProgsForTopic($ID);



if($max > 0) {

  $limit = $page->splitList($max, "?ID=$ID");



  $result = $vocabularies->getProgsForTopic($ID, $limit["from"], $limit["maxresults"]);



  // cache icons for results

  for($i=0; $i<count($result); $i++) {

	 $result[$i]['icon'] = sotf_Blob::cacheIcon2($result[$i]);

  }

}



$smarty->assign("ID", $ID);						//topic id

$smarty->assign("NAME", $NAME);						//topic name

$smarty->assign("query", $query);					//query

$smarty->assign("result", $result);					//result array



$page->send(); //no frames nescessary in this version

?>
