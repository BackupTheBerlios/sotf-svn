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

$page->popup = true;

//TODO: test
//$name = sotf_Utils::magicQuotes($_GET["name"]);
//$id = sotf_Utils::magicQuotes($_GET["id"]);
//$value = sotf_Utils::magicQuotes($_GET["value"]);

$name = $_GET["name"];
$id = $_GET["id"];
$value = $_GET["value"];

if ($name == 'rating') {
  $rating = new sotf_Rating();
  $rating->setRating($id, $value);
  $page->alertWithErrors();
  print("</body></html>");
  exit;
}

$page->forceLogin();

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
	$repository->addToTopic($id, $value);
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
  $playlist = new sotf_Playlist;
  $playlist->add($id);
	print("kesz");
} else print("<script type=\"text/javascript\" language=\"javascript1.1\">error();</script>");

$page->alertWithErrors();

?>
</body>
</html>
