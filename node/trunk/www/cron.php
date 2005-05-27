<?php
/*  -*- tab-width: 3; indent-tabs-mode: 1; -*-
 * $Id$
 *
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");
require_once($config['classdir'] . "/rpc_Utils.class.php");

checkAdminAccess();
excludeRobots();

/** This page has to be called periodically (e.g. using wget) and it
 *  performs all periodic maintenance tasks for the node server
*/

function line($msg) { // just for screen output (testing)
  echo "<br>$msg\n";
}

?>
<html>
<head><title><?php echo $config['nodeId']?> CRON</title></head>
<body>
<?php 

while (@ob_end_flush());

debug("--------------- CRON STARTED -----------------------------------");
line("CRON STARTED");

// this can be long duty!
set_time_limit(18000);
// don't garble reply message with warnings in HTML
//ini_set("display_errors", 0);

//******** Perform expensive updates on objects

sotf_Object::doUpdates();
line("UPDATES FINISHED");

//******** Synchronize with network: send new local data and forward new remote data

// sync with all neighbours
$rpc = new rpc_Utils;
$neighbours = sotf_Neighbour::listAll();
//debug("neighbours", $neighbours);
if(count($neighbours) > 0) {
  while(list(,$neighbour) = each($neighbours)) {
    $neighbour->sync();
  }
}

line("SYNC FINISHED");

//******** Forward messages to remote nodes 

// for all nodes
$nodes = sotf_Node::listAll();
if(count($nodes) > 0) {
  while(list(,$node) = each($nodes)) {
    if($node->get('node_id') != $config['nodeId']) {
      $node->forwardObjects();
    }
  }
}

line("FORWARD FINISHED");

//********* IMPORT ARRIVED XBMF
//if(false) {
$dirPath = $config['xbmfInDir'];
$dir = dir($dirPath);
while($entry = $dir->read()) {
	if ($entry != "." && $entry != "..") {
		$currentFile = $dirPath . "/" .$entry;
		if (!is_dir($currentFile)) {
			$XBMF[] = basename($currentFile);
		}
	}
}
$dir->close();
if(!empty($XBMF)) {
  foreach($XBMF as $xbmfFile) {
    $id = sotf_Programme::importXBMF($config['xbmfInDir'] . "/$xbmfFile", $config['publishXbmf']);
    if($id) {
      debug("CRON","Imported new XBMF: $xbmfFile");
      unlink($config['xbmfInDir'] . "/$xbmfFile");
    } else {
      logger("CRON","Import FAILED for XBMF: $xbmfFile");
    }
  }
}
//}

line("XBMF IMPORT FINISHED");

//******** Expire old programmes

  /*
$prgIds = sotf_Programme::getExpiredProgrammes();
if(!empty($prgIds)) {
  debug("deleting expired", $prgIds);
  $db->begin();
  foreach($prgIds as $id) {
    $prg = & $repository->getObject($id);
    $prg->delete();
  }
  $db->commit();
}
line("EXPIRED OLD SHOWS");
  */

//******** Update topic counts

debug("updating", "topic counts");
$vocabularies->updateTopicCounts();

line("TOPIC COUNTS UPDATED");

//******** Clean caches and tmp dirs

debug("cleaning", "tmpDir");
$clearTime = time() - 24*60*60;
$dir = dir($config['tmpDir']);
while($entry = $dir->read()) {
  if ($entry == "." || $entry == "..")
    continue;
  $file = $config['tmpDir'] . "/$entry";
  if(is_dir($file))
    continue;
  //if (preg_match('/\.png$/', $entry) || preg_match('/\.m3u$/', $entry)) {
  if(is_file($file) && filemtime($file) < $clearTime) {
    if(!unlink($file))
      logError("could not delete: $file");
  }
}
$dir->close();

line("CLEANED TMP DIR");

debug("cleaning", "cacheDir");
$clearTime = time() - 60*60;
$dir = dir($config['cacheDir']);
while($entry = $dir->read()) {
  if ($entry == "." || $entry == "..")
    continue;
  $file = $config['cacheDir'] . "/$entry";
  if(is_dir($file))
    continue;
  if(filemtime($file) < $clearTime) {
    if(!unlink($file))
      logError("could not delete: $file");
  }
}
$dir->close();

line("CLEANED CACHE DIR");

// TODO update subject tree language availability

// TODO remove old sotf_delete objects

// ******** Stop old streams

$playlist = new sotf_Playlist();
$playlist->stopOldStreams();

line("CRON FINISHED");

stopTiming();
$page->logRequest();
debug("--------------- CRON FINISHED -----------------------------------");
//echo "<h4>Cron.php completed</h4>";