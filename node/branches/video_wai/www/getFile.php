<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id: getFile.php 576 2006-05-25 19:04:01Z buddhafly $
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

$filename = sotf_Utils::getParameter('filename');
if(!$filename)
  $filename = sotf_Utils::getParameter('f');
$id = sotf_Utils::getParameter('id');
$fid = sotf_Utils::getParameter('fid');
$mainAudio = sotf_Utils::getParameter('audio');

if(empty($fid)) {
  if(empty($id)) {
	 raiseError("Missing parameters!", 'id');
  }
  if(empty($filename)) {
	 raiseError("Missing parameters!", 'filename');
  }
}

if($fid) {
  
  $pos = strpos($fid, ".mp3");
  if ($pos !== false) { $fid = rtrim($fid, ".mp3"); }
  
  
  debug ("fid", $fid);
  $fobj = &$repository->getObject($fid);
  if(!$fobj)
	 raiseError("no_such_object", $fid);
  $prg = $repository->getObject($fobj->get('prog_id'));
  $mainAudio = $fobj->get('main_content') == 't';
  $filename = $fobj->get('filename');
} else {
  $prg = $repository->getObject($id);
}

if(!$prg)
  raiseError("no_such_object", $id);

if(!$prg->isLocal()) {
  // have to send user to home node of this programme
  sotf_Node::redirectToHomeNode($prg, 'getFile.php');
  exit;
}

// TODO check if user have rights to access: 1. prg is published, 2. file has public_access or donwload_access

$pure_filename=$filename;

if($mainAudio)
     $filename =  sotf_Utils::getFileInDir($prg->getAudioDir(), $filename);
else
     $filename =  sotf_Utils::getFileInDir($prg->getOtherFilesDir(), $filename);

if(!is_readable($filename))
  raiseError("File not readable", $filename);

debug('filename', $filename);

$file = & new sotf_File($filename);

if (preg_match("/image/", $file->mimetype))
	{
		header("Content-type: " . $file->mimetype . "\n");
		header("Content-length: " . filesize($filename) . "\n");   
		//if($mainAudio) {  //this is somehow needed for iPodder
		//  header("Accept-Ranges: bytes");
		//  header('ETag: "' . md5(file_get_contents($filename)) . '"');
		//} else {
		  header("Content-transfer-encoding: binary\n"); 
		  //}
		// send file
		
		// wreutz: added this to get rid of fid_123mf12 filename and save as the real filename of the file
		header( "Content-Disposition: filename=".basename($filename).";\n" );
		// wreutz: end
	
		readfile($filename);

	}


else if ($file->type != "none"){
	if(!is_file($config['wwwdir'].'/tmp/'.$pure_filename)){
		symlink($filename, $config['wwwdir'].'/tmp/'.$pure_filename);
	}
	// add this download to statistics
	if($file->mimetype!='video/x-flv') $prg->addStat($file->id, "downloads");

	$page->redirect('http://' . $_SERVER['HTTP_HOST'] . $config['localPrefix'] . '/tmp/'.$pure_filename);
}

else  raiseError("download_problem", $filename);




$page->logRequest();

?>