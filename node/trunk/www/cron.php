<?php
/*  -*- tab-width: 3; indent-tabs-mode: 1; -*-
 * $Id$
 *
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");
require_once("$classdir/rpc_Utils.class.php");

/** This page has to be called periodically (e.g. using wget) and it
 *  performs all periodic maintenance tasks for the node server
*/

function line($msg) { // just for screen output (testing)
  echo "<br>$msg\n";
}

?>
<html>
<head><title><?php echo $nodeId?> CRON</title></head>
<body>
<?php 

debug("--------------- CRON STARTED -----------------------------------");

// this can be long duty!
set_time_limit(18000);
// don't garble reply message with warnings in HTML
//ini_set("display_errors", 0);

//******** Synchronize with network: send new local data and recievie new global data

// get sync stamp and increment it
$syncStamp = $sotfVars->get('sync_stamp', 0);
$syncStamp++;
$sotfVars->set('sync_stamp', $syncStamp);

// sync with all neighbours
$rpc = new rpc_Utils;
$neighbours = sotf_Neighbour::getAll();
if(count($neighbours) > 0) {
  while(list(,$neighbour) = each($neighbours)) {
      $neighbour->sync();
  }
}

//********* IMPORT ARRIVED XBMF
$dirPath = $xbmfInDir;
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
foreach($XBMF as $xbmfFile) {
	$id = sotf_Programme::importXBMF("$xbmfInDir/$xbmfFile");
	if($id) {
		debug("CRON","Imported new XBMF: $xbmfFile");
    unlink("$xbmfInDir/$xbmfFile");
	} else {
    logger("CRON","Import FAILED for XBMF: $xbmfFile");
	}
}


//******** Expire old programmes

// *** regenerate metadata files??

//******** Update topic counts

//******** Clean caches adn tmp dirs

// remove m3us and pngs from tmpdir



stopTiming();
$page->logRequest();
debug("--------------- CRON FINISHED -----------------------------------");
echo "<h4>Cron.php completed</h4>";