<?php

require("init.inc.php");

$id = sotf_Utils::getParameter('id');

$obj = $repository->getObject($id);

$image = $obj->getLogo();

if($image)
{
	header("Content-type: image/png\n");
	header("Content-transfer-encoding: binary\n"); 
	header("Content-length: " . strlen($image) . "\n");   

	// send file
	echo($image);
}
else
	exit($page->getlocalized("dowload_problem"));

?>