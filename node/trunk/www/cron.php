<?php
require("init.inc.php");
require_once("$classdir/rpc_Utils.class.php");

/** This page has to be called periodically (e.g. using wget) and it performs all periodic maintenance tasks for the node server */

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
    $remoteNode = sotf_Node::getNodeById($neighbour->get('node_id'));
    $localNode = sotf_Node::getLocalNode();
    // collect local data to send
    $objs = sotf_NodeObject::getModifiedObjects($neighbour->get('last_outgoing'));
    $response = $rpc->call($remoteNode->get('url') . '/xmlrpcServer.php', 'sync', array($localNode->getAll(), $objs));
    // error handling!!!
    if(!$response) {
      logError("SYNC failed with node " . $neighbour->get("node_id"));
      continue;
    }
    // save received data
    if(count($response) > 0) {
      sotf_NodeObject::saveModifiedObjects($objects);
    }
    // save last_sync
    $neigbour->set('last_outgoing', $timestamp);
    $neigbour->update();
    // send receipt of successful sync??
  }
}

//******** Expire old programmes



//******** Update topic counts

//******** Clean caches ???

stopTiming();
$page->logRequest();
debug("--------------- CRON FINISHED -----------------------------------");
echo "<h4>Cron.php completed</h4>";