<?php
require("init.inc.php");
require_once("$classdir/rpc_Utils.class.php");

/** This page has to be called periodically (e.g. using wget) and it performs all periodic maintenance tasks for the node server */

function line($msg) { // just for screen output (testing)
  echo "<br>$msg\n";
}

?>
<html>
<head><title><?php echo $nodeId?> CRON</title></head>
<body>
<?php 

debug("--------------- CRON STARTED -----------------------------------");

//******** Synchronize with network: send new local data and recievie new global data

$rpc = new rpc_Utils;
$neighbours = sotf_Neighbour::getAll();
if(count($neighbours) > 0) {
  while(list(,$neighbour) = each($neighbours)) {
    debug("CRON", "syncing with ". $neighbour->get("node_id"));
    $neighbour->sync();
  }
}

//******** Expire old programmes

// *** regenerate metadata files??

//******** Update topic counts

//******** Clean caches ???

stopTiming();
$page->logRequest();
debug("--------------- CRON FINISHED -----------------------------------");
echo "<h4>Cron.php completed</h4>";