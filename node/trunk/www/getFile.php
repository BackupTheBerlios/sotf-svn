<?php

require("init.inc.php");

$filename = sotf_Utils::getParameter('filename');
$id = sotf_Utils::getParameter('id');
$mainAudio = sotf_Utils::getParameter('audio');
$prg = & new sotf_Programme($id);

$filename = sotf_Utils::getFileFromPath($filename);
if($mainAudio)
     $filename = $prg->getAudioDir() . '/' . $filename;
else
     $filename = $prg->getOtherFilesDir() . '/' . $filename;


$file = & new sotf_File($filename);
if ($file->type != "none")
{
	header("Content-type: " . $file->mimetype . "\n");
	header("Content-transfer-encoding: binary\n"); 
	header("Content-length: " . filesize($filename) . "\n");   

	// send file
	readfile($filename);
}
else
	exit($page->getlocalized("dowload_problem"));

?>