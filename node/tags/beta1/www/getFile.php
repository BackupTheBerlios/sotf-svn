<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

$filename = sotf_Utils::getParameter('filename');
$id = sotf_Utils::getParameter('id');
$mainAudio = sotf_Utils::getParameter('audio');
$prg = & new sotf_Programme($id);

// TODO check if user have rights to access: 1. prg is published, 2. file has public_access or donwload_access

if($mainAudio)
     $filename =  sotf_Utils::getFileInDir($prg->getAudioDir(), $filename);
else
     $filename =  sotf_Utils::getFileInDir($prg->getOtherFilesDir(), $filename);

if(!is_readable($filename))
     raiseError("File not readable: $filenameOrig");

debug('filename', $filename);

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