<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

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

$page->logRequest();

?>