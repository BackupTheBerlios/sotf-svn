<?php
require("init.inc.php");
$page->forceLogin();
//sotf_Utils::getParameter("");

if (sotf_Utils::getParameter("close") == "true")
{	
	$lista = sotf_Utils::getParameter("lista");
	$list = split("\|", $lista);
	$max = count($list);
	for($i=1; $i<$max; $i++)
	{
		$l = split(":", $list[$i]);
		if ($l[1] != $i)
		{
			//print($l[0].":".$l[1]." -> ".$i."<br>");
			$query="UPDATE sotf_playlists SET order_id = ".$i." WHERE user_id = ".$user->id." AND order_id = ".$l[1]." AND prog_id = '".$l[0]."'";
			$result = $db->query($query);
		}

	}
	$page->redirect("closeAndRefresh.php");
	//die("<HTML><HEAD></HEAD><BODY onload='javascript:window.opener.location.reload();window.close();'></BODY></HTML>");
}


$query="SELECT prog_id as id, order_id, title, icon FROM sotf_playlists".
	" LEFT JOIN sotf_programmes ON sotf_programmes.id = sotf_playlists.prog_id".
	" WHERE user_id = ".$user->id." ORDER BY order_id";
$result = $db->getAll($query);

print("<pre>");
//var_dump($result);
print("</pre>");

/*
<xsl:for-each select="form/task">
  <option value="{level}:{oid}">
	<xsl:value-of select="name"/>
  </option>
</xsl:for-each>
*/

$programmes = array();
foreach($result as $value)
{
	$programmes[$value["id"].":".$value["order_id"]] = $value["title"];
}

$smarty->assign("result", $result);
$smarty->assign("count", count($result));
$smarty->assign("programmes", $programmes);

$page->sendPopup("playlistPopup.htm");
?>