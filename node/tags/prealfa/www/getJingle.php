<?php
require("init.inc.php");

$id = sotf_Utils::getParameter('id');
$index = sotf_Utils::getParameter('index');

$obj = $repository->getObject($id);

$jingleFile = $obj->getJingle($index);

if($jingleFile)
{
  $jingle = new sotf_AudioFile($jingleFile);
	header("Content-type: $jingle->mimetype\n");
	header("Content-transfer-encoding: binary\n"); 
	header("Content-length: " . filesize($jingleFile) . "\n");   

	// send file
	readfile($jingleFile);
}
else
	raiseError($page->getlocalized("download_problem"));

?>