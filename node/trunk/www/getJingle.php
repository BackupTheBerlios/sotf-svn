<?php  // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

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