<?php

require("init.inc.php");

$station = sotf_Utils::getParameter('station');
$index = sotf_Utils::getParameter('index');

$st = & new sotf_Station($station);
$jingle = $st->getJingle($index);
if (false !== $jingle)
{
	$audiofile = & new sotf_AudioFile($jingle);

	if($audiofile->type == "audio")
	{
		header("Content-type: " . $audiofile->mimetype . "\n");
		header("Content-transfer-encoding: binary\n"); 
		header("Content-length: " . filesize($jingle) . "\n");   
	
		// send file
		readfile($jingle);
	}
	else
		exit($page->getlocalized("dowload_problem"));
}
else
	exit($page->getlocalized("dowload_problem"));

?>