<?php
require("init.inc.php");
$page->forceLogin();
//sotf_Utils::getParameter("");

if (sotf_Utils::getParameter("close") == "true")
{
	var_dump($_POST);
	//$page->redirect("closeAndRefresh.php");
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
foreach($result as $key => $value)
{
	$programmes["0:".$key] = $value["title"];
}

$smarty->assign("result", $result);
$smarty->assign("count", count($result));
$smarty->assign("programmes", $programmes);

$page->sendPopup("playlistPopup.htm");
?>