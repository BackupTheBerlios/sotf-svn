<?php
require("init.inc.php");
require_once("$classdir/rpc_Utils.class.php");

/** This page is the service point for XML-RPC calls arriving to the node */

debug("--------------- XML-RPC SERVER STARTED -----------------------------------");

$map['sotf.sync'] = array('function' => 'syncResp');
//$map['sotf.allIds'] = array('function' => 'allIds');
//$map['sotf.getById'] = array('function' => 'getById');
//$map['sotf.ref'] = array('function' => 'itemReference');
//$map['sotf.comment'] = array('function' => 'itemComment');
//$map['sotf.nodeForID'] = array('function' => 'getNodeForID');

new xmlrpc_server($map);

function syncResp($params) {
  $nodeData = xmlrpc_decode($params->getParam(0));
  $objects = xmlrpc_decode($params->getParam(1));
  $timestamp = db_Wrap::getTimestampTz();
  $neighbour = sotf_Neighbour::getById($nodeData['node_id']);
  // TODO check access
  // save modified objects
  sotf_NodeObject::saveModifiedObjects($objects);
  // get new objects to send as reply
  $objects = sotf_NodeObject::getModifiedObjects($neighbour->get('last_outgoing'), false);
  // save time of this sync
  $neigbour->set('last_outgoing', $timestamp);
  $neigbour->update();
  // send response
  $objects = xmlrpc_encode($objects);
  return new xmlrpcresp($objects);
}


stopTiming();
$page->logRequest();
debug("--------------- XML-RPC SERVER FINISHED -----------------------------------");
