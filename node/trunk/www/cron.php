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

debug("--------------- CRON STARTED -----------------------------------");

// this can be long duty!
set_time_limit(18000);
// don't garble reply message with warnings in HTML
//ini_set("display_errors", 0);

//******** Perform expensive updates on objects

sotf_Object::doUpdates();

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

//********* IMPORT ARRIVED XBMF

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


//******** Expire old programmes

// *** regenerate metadata files??

//******** Update topic counts

//******** Clean caches adn tmp dirs

// remove m3us and pngs from tmpdir

// update subject tree language availability



stopTiming();
$page->logRequest();
debug("--------------- CRON FINISHED -----------------------------------");
echo "<h4>Cron.php completed</h4>";