<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */


require("init.inc.php");

$id = sotf_Utils::getParameter('id');

$obj = $repository->getObject($id);

$image = $obj->getIcon();

if($image)
{
	header("Content-type: image/png\n");
	header("Content-transfer-encoding: binary\n"); 
	header("Content-length: " . strlen($image) . "\n");   

	// send file
	echo($image);
}
else
	raiseError($page->getlocalized("dowload_problem"));

$page->logRequest();

?>