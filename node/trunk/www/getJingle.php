<?php  // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

$id = sotf_Utils::getParameter('id');
$file = sotf_Utils::getParameter('file');

$obj = $repository->getObject($id);

$jingleFile = sotf_Utils::getFileInDir($obj->getJingleDir(), $file);
debug("jingleFile", $jingleFile);

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