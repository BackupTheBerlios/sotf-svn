<?php
require("init.inc.php");
require_once("$classdir/rpc_Utils.class.php");

/** This page is the service point for XML-RPC calls arriving to the node */

define("XMLRPC_ERR_NO_ACCESS", $xmlrpcerruser+2);

debug("--------------- XML-RPC SERVER STARTED -----------------------------------");

$map['sotf.sync'] = array('function' => 'syncResp');
//$map['sotf.allIds'] = array('function' => 'allIds');
//$map['sotf.getById'] = array('function' => 'getById');
//$map['sotf.ref'] = array('function' => 'itemReference');
//$map['sotf.comment'] = array('function' => 'itemComment');
//$map['sotf.nodeForID'] = array('function' => 'getNodeForID');

new xmlrpc_server($map);

function checkAccess($neighbour) {
  $url = $this->getUrl();
  if(!$url)
    return "No url found for neighbour node";
  $parsed = parse_url($url);
  $allowedIPs = gethostbynamel($parsed['host']);
  $ip = getenv("REMOTE_ADDR");
  if(!in_array($ip, $allowedIPs)) {
    logError(getenv('REMOTE_HOST') . " XML-RPC access denied");
    return "this IP is not from neighbour " . $neighbour->get('node_id');
  }
}

function syncResp($params) {
  $lastSync = xmlrpc_decode($params->getParam(0));
  $nodeData = xmlrpc_decode($params->getParam(1));
  $objects = xmlrpc_decode($params->getParam(2));
  $neighbour = sotf_Neighbour::getById($nodeData['node_id']);
  if(!$neighbour)
    return new xmlrpcresp(0, XMLRPC_ERR_NO_ACCESS, "No access: you are not an allowed neighbour node!");
  $msg = checkAccess($enighbour);
  if($msg)
    return new xmlrpcresp(0, XMLRPC_ERR_NO_ACCESS, "No access: $msg!");
  $retval = $neighbour->syncResponse($lastSync, $nodeData, $objects);
  // send response
  $retval = xmlrpc_encode($retval);
  return new xmlrpcresp($retval);
}


stopTiming();
$page->logRequest();
debug("--------------- XML-RPC SERVER FINISHED -----------------------------------");
