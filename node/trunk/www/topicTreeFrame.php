<?php
require("init.inc.php");

$smarty->assign("openTree", sotf_Utils::getParameter("open"));

function SORTIT($a, $b)
{
	if (($a["supertopic"] != 0) AND ($b["supertopic"] != 0))	//both are leafs
	{
		$sabc = strcmp($a["supertopic"], $b["supertopic"]);
		if ($sabc != 0) return $sabc;
		return strcmp($a["name"], $b["name"]);
	}
	elseif (($a["supertopic"] == 0) AND ($b["supertopic"] != 0))	//first is root second leaf
	{
		$sabc = strcmp($a["id"], $b["supertopic"]);
		if ($sabc != 0) return $sabc;
		return -1;
	}
	elseif (($a["supertopic"] != 0) AND ($b["supertopic"] == 0))	//first is leaf second root
	{
		$sabc = strcmp($a["supertopic"], $b["id"]);
		if ($sabc != 0) return $sabc;
		return +1;
	}
	elseif (($a["supertopic"] == 0) AND ($b["supertopic"] == 0))	//both are roots
	{
		return strcmp($a["id"], $b["id"]);
	}
}

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


$query = "SELECT sotf_topic_tree_defs.*, sotf_topics.topic_name, sotf_topics_counter.number from sotf_topic_tree_defs".
		" LEFT JOIN sotf_topics ON sotf_topics.topic_id = sotf_topic_tree_defs.id".
		" LEFT JOIN sotf_topics_counter ON sotf_topics_counter.topic_id = sotf_topic_tree_defs.id";
$result = $db->getAll($query);

usort($result, "SORTIT");

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