<html>
<head>
<title>Sending data</title>
</head>
<body onload="javascript:windowclose();return true;">
<script type="text/javascript" language="javascript1.1">
var noerror = 1;
function error()
{
document.write("Error on page.");
noerror = 0;
}
function windowclose()
{
	if (noerror) window.close();
}
</script>
<?php
//<body>
require("init.inc.php");
$page->forceLogin();

$name = $_GET["name"];
$id = $_GET["id"];
$value = $_GET["value"];

//var_dump($_GET);
//die();
print("Name=".$name."<br>ID=".$id."<br>Value=".$value);

if ($name == "links")		//editFiles
{
	$x = new sotf_NodeObject("sotf_links", $id);
	if ($value == 'true') $x->set('public_access', 'true');
	elseif  ($value == 'false') $x->set('public_access', 'false');
	else print("<script type=\"text/javascript\" language=\"javascript1.1\">error();</script>");
	$x->update();
	print("kesz");
}
elseif ($name == "otherfiles")		//editFiles
{
	$x = new sotf_NodeObject("sotf_other_files", $id);
	if ($value == 'true') $x->set('public_access', 'true');
	elseif  ($value == 'false') $x->set('public_access', 'false');
	else print("<script type=\"text/javascript\" language=\"javascript1.1\">error();</script>");
	$x->update();
	print("kesz");
}
elseif ($name == "audiofilesa")		//editFiles
{
	$x = new sotf_NodeObject("sotf_media_files", $id);
	if ($value == 'true') $x->set('stream_access', 'true');
	elseif  ($value == 'false') $x->set('stream_access', 'false');
	else print("<script type=\"text/javascript\" language=\"javascript1.1\">error();</script>");
	$x->update();
	print("kesz");
}
elseif ($name == "audiofilesd")		//editFiles
{
	$x = new sotf_NodeObject("sotf_media_files", $id);
	if ($value == 'true') $x->set('download_access', 'true');
	elseif  ($value == 'false') $x->set('download_access', 'false');
	else print("<script type=\"text/javascript\" language=\"javascript1.1\">error();</script>");
	$x->update();
	print("kesz");
}
elseif ($name == "caption")		//editFiles
{
	$x = new sotf_NodeObject("sotf_other_files", $id);
	$x->set('caption', addslashes($value));
	$x->update();
	print("kesz");
}
elseif ($name == "addtree")		//topic_tree
{
	$x = new sotf_NodeObject("sotf_prog_topics");
	$x->set('prog_id', addslashes($id));
	$x->set('topic_id', addslashes($value));
	$x->create();
	print("kesz");
}
elseif ($name == "editorpub")		//editor bublished checkboxes
{
	$x = new sotf_Programme($id);
	if ($value == 'true') $x->publish();
	elseif  ($value == 'false') $x->withdraw();
	else print("<script type=\"text/javascript\" language=\"javascript1.1\">error();</script>");
	print("kesz");
}
elseif ($name == "addplaylist")		//get.htm add a programm to the playlist
{
	$query="SELECT prog_id, order_id FROM sotf_playlists WHERE user_id=".$user->id." AND prog_id='".$id."'";
	$result = $db->getAll($query);		//test if already in the playlist
	//var_dump($result);
	if (count($result) == 0)
	{
		$query="SELECT prog_id, order_id FROM sotf_playlists WHERE user_id=".$user->id." ORDER BY order_id DESC";
		$result = $db->getAll($query);		//get biggest id
		//var_dump($result);
		if (count($result) > 0) $next = $result[0]["order_id"] + 1;	//set id to biggest+1
		else $next = 0;
		$query="INSERT INTO sotf_playlists (prog_id, user_id, order_id) VALUES ('".$id."', ".$user->id.", ".$next.")";
		//rint($query);
		$result = $db->query($query);
	}
	print("kesz");
}
else print("<script type=\"text/javascript\" language=\"javascript1.1\">error();</script>");
?>
</body>
</html>
