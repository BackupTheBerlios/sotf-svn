<?php

require("init.inc.php");

$filename = sotf_Utils::getParameter('filename');

$filename = sotf_Utils::getFileInDir($user->getUserDir(), $filename);

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
	raiseError("download_problem");

?>