<?php
require("init.inc.php");
$page->forceLogin();
//sotf_Utils::getParameter("");

if (sotf_Utils::getParameter("delete_selected") != "")
{
	$checkbox = sotf_Utils::getParameter("checkbox");
	$max =  count($checkbox);
	for($i=0; $i<$max; $i++)
	{
		$query="DELETE FROM sotf_playlists WHERE user_id = ".$user->id." AND prog_id='".$checkbox[$i]."'";
		$result = $db->query($query);
	}
	$page->redirect("playlist.php");
}

$query="SELECT prog_id as id, order_id, sotf_programmes.* FROM sotf_playlists".
	" LEFT JOIN sotf_programmes ON sotf_programmes.id = sotf_playlists.prog_id".
	" WHERE user_id = ".$user->id." ORDER BY order_id";
$result = $db->getAll($query);

print("<pre>");
//var_dump($result);
print("</pre>");

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