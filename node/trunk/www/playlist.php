<?php
require("init.inc.php");
$page->forceLogin();
//sotf_Utils::getParameter("");

$playlist = new sotf_Playlist();

if (sotf_Utils::getParameter("delete_selected") != "")
{
	$checkbox = sotf_Utils::getParameter("checkbox");
	$max =  count($checkbox);
	for($i=0; $i<$max; $i++)
	{
    $playlist->delete($checkbox[$i]);
	}
	$page->redirect("playlist.php");
}

$result = $playlist->load();

$programmes = array();
foreach($result as $key => $value)
{
  sotf_ComplexNodeObject::cacheIcon($value['id'], $db->unescape_bytea($value['icon']));
	$programmes["0:".$key] = $value["title"];
}

$smarty->assign("result", $result);
$smarty->assign("count", count($result));
$smarty->assign("programmes", $programmes);

$page->send();



?>