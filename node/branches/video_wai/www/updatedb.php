<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 



/*  

 * $Id: updatedb.php 386 2005-06-24 14:33:06Z wreutz $

 * Created for the StreamOnTheFly project (IST-2001-32226)

 * Authors: András Micsik, Máté Pataki, Tamás Déri 

 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu

 */



require("init.inc.php");



$page->popup = true;



//TODO: test

//$name = sotf_Utils::magicQuotes($_GET["name"]);

//$id = sotf_Utils::magicQuotes($_GET["id"]);

//$value = sotf_Utils::magicQuotes($_GET["value"]);



$name = $_GET["name"];

$id = $_GET["id"];

$value = $_GET["value"];



?>



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



if ($name == 'rating') {

  $rating = new sotf_Rating();

  $obj = $repository->getObject($id);

  if($obj->isLocal()) {

	 $rating->setRating($id, $value);

	 $page->alertWithErrors();

	 print("</body></html>");

	 exit;

  } else {

	 // rating for remote object

	 $rating->sendRemoteRating($obj, $value);

	 exit;

  }

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

	print("success");

}

elseif ($name == "otherfiles")		//editFiles

{

	$x = new sotf_NodeObject("sotf_other_files", $id);

	if ($value == 'true') $x->set('public_access', 'true');

	elseif  ($value == 'false') $x->set('public_access', 'false');

	else print("<script type=\"text/javascript\" language=\"javascript1.1\">error();</script>");

	$x->update();

	print("success");

}

elseif ($name == "audiofilesa")		//editFiles

{

	$x = new sotf_NodeObject("sotf_media_files", $id);

	if ($value == 'true') $x->set('stream_access', 'true');

	elseif  ($value == 'false') $x->set('stream_access', 'false');

	else print("<script type=\"text/javascript\" language=\"javascript1.1\">error();</script>");

	$x->update();

	print("success");

}

elseif ($name == "audiofilesd")		//editFiles

{

	$x = new sotf_NodeObject("sotf_media_files", $id);

	if ($value == 'true') $x->set('download_access', 'true');

	elseif  ($value == 'false') $x->set('download_access', 'false');

	else print("<script type=\"text/javascript\" language=\"javascript1.1\">error();</script>");

	$x->update();

	print("success");

}

elseif ($name == "caption")		//editFiles

{

	$x = new sotf_NodeObject("sotf_other_files", $id);

	$x->set('caption', addslashes($value));

	$x->update();

	print("success");

}

elseif ($name == "addtree")		//topic_tree

{

	$vocabularies->addToTopic($id, $value);

	// doesnt work: print("<script type=\"text/javascript\">window.opener.opener.reload();</script>");

	print("success");

}

elseif ($name == "editorpub")		//editor bublished checkboxes

{

	$x = new sotf_Programme($id);

	if ($value == 'true') $x->publish();

	elseif  ($value == 'false') $x->withdraw();

	else print("<script type=\"text/javascript\" language=\"javascript1.1\">error();</script>");

	print("success");

}

elseif ($name == "editorflag")		//editor programme flags

{

	$x = new sotf_Object("sotf_user_progs");

	$x->set('user_id', $user->id);

	$x->set('prog_id', $id);

	$x->find();

	$x->set('flags', $value);

	if ($x->id)

	{

		if ($value == "none") $x->delete();

		else $x->update();

	}

	else $x->create();

//	else print("<script type=\"text/javascript\" language=\"javascript1.1\">error();</script>");

	print("success");

}

elseif ($name == "addplaylist")		//get.htm add a programm to the playlist

{

  $playlist = new sotf_UserPlaylist;

  $playlist->add($id);

	print("success");

} else print("<script type=\"text/javascript\" language=\"javascript1.1\">error();</script>");



$page->alertWithErrors();



?>

</body>

</html>

