<?php

require("init.inc.php");

$station = sotf_Utils::getParameter('station');

$st = & new sotf_Station($station);
$image = $st->getLogo();
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