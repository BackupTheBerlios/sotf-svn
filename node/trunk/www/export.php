<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

$id = sotf_Utils::getParameter('id');
$type = sotf_Utils::getParameter('type');
$prg = & new sotf_Programme($id);

if(!$prg)
	  raiseError("no_such_object");

if(!$prg->isLocal()) {
  raiseError("works only for local objects!");
  exit;
}

checkPerm($prg, 'change');

if ($type == 1) {
  // send XBMF metadata
  $md = $prg->getXBMFMetadata();

  header("Content-type: application/xml\n");
  //header("Content-type: text/plain\n");
  //header("Content-transfer-encoding: binary\n"); 
  header("Content-length: " . strlen($md) . "\n");   
  echo $md;

} elseif($type == 2) {
  // actualize metadata
  $prg->saveMetadataFile();
  // send XBMF
  $file =  tempnam($config['tmpDir'],'export');
  $dir = $prg->getDir();
  $dir1 = basename($dir);
  $dir2 = dirname($dir);
  $xdir = "XBMF_$dir1";
  // TODO: this only works on Unix/Linux
  chdir($dir2);
  debug("chdir", $dir2);
  if(!symlink($dir1, $xdir)) {
	 raiseError("could not create hard link");
  }
  system("tar cfh $file $xdir");
  unlink($xdir);
  header("Content-type: application/x-tar\n");
  header("Content-transfer-encoding: binary\n"); 
  header("Content-length: " . filesize($file) . "\n");
  header("Content-Disposition: attachment; filename=$xdir.tar");
  readfile($file);
  unlink($file);
}


$page->logRequest();

?>