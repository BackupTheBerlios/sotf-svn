<?php  // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

$smarty->assign("openTree", sotf_Utils::getParameter("open"));

$parent = sotf_Utils::getParameter('parent');
$name = sotf_Utils::getParameter('name');
$topic_id = sotf_Utils::getParameter('topic_id');
$topic_name = sotf_Utils::getParameter('topic_name');
$topic_counter = sotf_Utils::getParameter('topic_counter');
$find = sotf_Utils::getParameter('find');
$add = sotf_Utils::getParameter('add');

if ($name != "")
{
	$x = new sotf_NodeObject("sotf_topic_tree_defs");
	$x->set('supertopic', $parent);
	$x->set('name', $name);
	$x->create();
	$id = $x->getID();
}

if ($topic_name != "")
{
	$x = new sotf_NodeObject("sotf_topics");
	$x->set('topic_id', $topic_id);
	$x->set('language', "en");
	$x->set('topic_name', $topic_name);
	$x->create();
	$id = $x->getID();
	print($id);
}

if ($topic_counter != "")
{
	$x = new sotf_Object("sotf_topics_counter");
	$x->set('topic_id', $topic_id);
	$x->set('number', $topic_counter);
	$x->create();
	$id = $x->getID();
	print($id);
}

// TODO: select lang and topic tree!!
$result = $repository->getTree(1, 'en', true);

$parentid;
$counter = 0;
$max = count($result);
for ($i=0; $i < $max; $i++)
{
	if ($result[$i]["supertopic"] == 0)
	{
		if ($i != 0) $result[$parentid]["numberall"] = $counter;
		$parentid = $i;
		$counter = 0;
	}
	$counter += $result[$i]["number"];
}

if ($find != "")
{
	$found	= array();
	$max = count($result);
	for ($i=0; $i < $max; $i++)
		if(strstr(strtolower($result[$i]["topic_name"]),$find)) $found[] = $result[$i]["id"];
	for ($i=0; $i < count($found); $i++) print($i.": ".$found[$i]." ");
}

$smarty->assign("result", $result);
//$page->send("main_frame_right.htm");
$page->send("topicTreeFrame.htm");
?>