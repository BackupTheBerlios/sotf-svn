<?php
require("init.inc.php");
$page->forceLogin();
//sotf_Utils::getParameter("");

$playlist = new sotf_Playlist();

if (sotf_Utils::getParameter("delete_selected") != "")			//delete selected button pressed
{
	$checkbox = sotf_Utils::getParameter("checkbox");
	$max =  count($checkbox);
	for($i=0; $i<$max; $i++)
	{
		$playlist->delete($checkbox[$i]);
	}
	$page->redirect("playlist.php");
}
if (sotf_Utils::getParameter("play_selected") != "")			//delete selected button pressed
{
	$playlistFiles = array();
	$checkbox = sotf_Utils::getParameter("checkbox");
	$max =  count($checkbox);
	for($i=0; $i<$max; $i++)
	{
		$playlistFiles[$checkbox[$i]] = $playlist->getFilename($checkbox[$i]);
	}
	print("<pre>");
	var_dump($playlistFiles);
	print("</pre>");
//	$page->redirect("playlist.php");
/*
	$filename = '';
	if (is_writable($filename))	// Let's make sure the file exists and is writable first.
	{
	    if (!$fp = fopen($filename, 'w'))
	    {
	         raiseError("Cannot open file ($filename)");
	         exit;
	    }
	    if (!fwrite($fp, $somecontent))	    // Write $somecontent to our opened file.
	    {
	        raiseError("Cannot write to file ($filename)");
	        exit;
	    }
	    fclose($fp);
	}
	else raiseError("The file $filename is not writable");
*/
}


$result = $playlist->load();

$programmes = array();
for($i=0; $i<count($result); $i++)
{
  $result[$i]['icon'] = sotf_Blob::cacheIcon($result[$i]['id']);
	$programmes["0:".$i] = $result[$i]["title"];
}

$smarty->assign("result", $result);
$smarty->assign("count", count($result));
$smarty->assign("programmes", $programmes);

$page->send();



?>