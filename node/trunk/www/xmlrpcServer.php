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
require_once($config['classdir'] . "/sotf_AdvSearch.class.php");

/** This page is the service point for XML-RPC calls arriving to the node */

define("XMLRPC_ERR_NO_ACCESS", $xmlrpcerruser+2);

// this can be long duty!
set_time_limit(18000);
// don't garble reply message with warnings in HTML
ini_set("display_errors", 0);

debug("--------------- XML-RPC SERVER STARTED -----------------------------------");

$map['sotf.sync'] = array('function' => 'syncResp');
$map['sotf.forward'] = array('function' => 'forwardResp');
$map['portal.query'] = array('function' => 'getQueryResults');
$map['portal.playlist'] = array('function' => 'getProgrammes');
$map['portal.events'] = array('function' => 'putEvents');
$map['sotf.cv.listnames'] = array('function' => 'cvListNames');
$map['sotf.cv.get'] = array('function' => 'cvGet');
//$map['sotf.allIds'] = array('function' => 'allIds');
//$map['sotf.getById'] = array('function' => 'getById');
//$map['sotf.ref'] = array('function' => 'itemReference');
//$map['sotf.comment'] = array('function' => 'itemComment');
//$map['sotf.nodeForID'] = array('function' => 'getNodeForID');

new xmlrpc_server($map);

function checkAccess($url, $nodeId) {
  if(!$url)
    return "No url found for neighbour node";
  $parsed = parse_url($url);
  $allowedIPs = gethostbynamel($parsed['host']);
  if(count($allowedIPs) == 0)
    logError("DNS does not work, cannor resolve host name!");
  $ip = getenv("REMOTE_ADDR");
  if(!in_array($ip, $allowedIPs)) {
    logError(getenv('REMOTE_HOST') . " XML-RPC access denied");
    return "this IP is not from neighbour " . $nodeId;
  }
}

function cvListNames($params) {
  global $vocabularies;
  debug("incoming XML-RPC request: sotf.cv.listnames");
  // TODO: check access
  $retval = $vocabularies->getCVocabularyNames();
  $retval = xmlrpc_encoder($retval);
  return new xmlrpcresp($retval);
}

function cvGet($params) {
  global $vocabularies;
  debug("incoming XML-RPC request: sotf.cv.get");
  // TODO: check access
  $type = xmlrpc_decoder($params->getParam(0));
  $name = xmlrpc_decoder($params->getParam(1));
  $lang = xmlrpc_decoder($params->getParam(2));
  $retval = $vocabularies->getCVocabulary($type, $name, $lang);
  $retval = xmlrpc_encoder($retval);
  return new xmlrpcresp($retval);
}

function syncResp($params) {
  debug("incoming SYNC request");
  $chunkInfo = xmlrpc_decoder($params->getParam(0));
  $nodeData = $chunkInfo['node'];
  $objects = xmlrpc_decoder($params->getParam(1));
  $neighbour = sotf_Neighbour::getById($nodeData['node_id']);
  if(!$neighbour) {
    logError("No access: you are not an allowed neighbour node!");
    return new xmlrpcresp(0, XMLRPC_ERR_NO_ACCESS, "No access: you are not an allowed neighbour node!");
  }
  $msg = checkAccess($neighbour->getUrl(), $neighbour->get('node_id'));
  if($msg) {
    logError($msg);
    return new xmlrpcresp(0, XMLRPC_ERR_NO_ACCESS, "No access: $msg!");
  }
  $retval = $neighbour->syncResponse($chunkInfo, $objects);
  // send response
  $retval = xmlrpc_encoder($retval);
  return new xmlrpcresp($retval);
}

function forwardResp($params) {
  debug("incoming FORWARD request");
  $chunkInfo = xmlrpc_decoder($params->getParam(0));
  $fromNode = $chunkInfo['from_node'];
  $objects = xmlrpc_decoder($params->getParam(1));
  $node = sotf_Node::getNodeById($fromNode);
  if(!$node) {
    logError("No access: you are not in my node list!");
    return new xmlrpcresp(0, XMLRPC_ERR_NO_ACCESS, "No access: you are not in my node list!");
  }
  $msg = checkAccess($node->get('url'), $fromNode);
  if($msg) {
    logError($msg);
    return new xmlrpcresp(0, XMLRPC_ERR_NO_ACCESS, "No access: $msg!");
  }
  $retval = $node->forwardResponse($chunkInfo, $objects);
  // send response
  $retval = xmlrpc_encoder($retval);
  return new xmlrpcresp($retval);
}

/** For portal */
function getQueryResults($params)
{
	global $config, $db;
	$query = xmlrpc_decoder($params->getParam(0));

	$advsearch = new sotf_AdvSearch();	//create new search object object with this array
	$SQLquery = $advsearch->Deserialize($query);		//deserialize the content of the hidden field
	$query = $advsearch->GetSQLCommand();
	$results = $db->getAll($query." LIMIT 30 OFFSET 0");
	foreach($results as $key => $result)
	{
		$icon = sotf_Blob::cacheIcon2($result);
		if ($icon) $results[$key]['icon'] = $config['cacheUrl']."/".$icon;
		//TODO if no icon {$IMAGEDIR}/noicon.png $imageprefix????

		$prg = & new sotf_Programme($result['id']);
		// audio files for programme
		$audioFiles = $prg->listAudioFiles('true');
		$results[$key]['audioFiles'] = array();
		$results[$key]['downloadFiles'] = array();
		foreach($audioFiles as $fileList)
		{
			if ($fileList['stream_access'] == "t") $results[$key]['audioFiles'][] = $fileList;
			if ($fileList['download_access'] == "t") $results[$key]['downloadFiles'][] = $fileList;
		}


	}
	$retval = xmlrpc_encoder($results);
	return new xmlrpcresp($retval);
}

/** For portal */
function getProgrammes($params)
{
	global $config, $db;
	$prglist = xmlrpc_decoder($params->getParam(0));

	$query="SELECT programmes.* FROM (";
	$query.=" SELECT sotf_programmes.*, sotf_stations.name as station, sotf_series.name as seriestitle, sotf_series.description as seriesdescription, sotf_prog_rating.rating_value as rating FROM sotf_programmes";
	$query.=" LEFT JOIN sotf_stations ON sotf_programmes.station_id = sotf_stations.id";
	$query.=" LEFT JOIN sotf_series ON sotf_programmes.series_id = sotf_series.id";
	$query.=" LEFT JOIN sotf_prog_rating ON sotf_programmes.id = sotf_prog_rating.id";
	$query.=") as programmes WHERE published = 't'";

	$results = array();

	foreach($prglist as $prg)
	{
//		debug("------------>".$prg."<------------------");
//		debug("------------>".$query." AND id = '$prg'<------------------");
		$p = $db->getRow($query." AND id = '$prg'");
		if ($p != NULL) $results[] = $p;
		//else $results[] = array("id" => $prg, "title" => "DELETED");

	}

	foreach($results as $key => $result)
	{
//		debug("------------>".$result['id']."<------------------");
		$icon = sotf_Blob::cacheIcon2($result);
		if ($icon) $results[$key]['icon'] = $config['cacheUrl']."/".$icon;
		//TODO if no icon {$IMAGEDIR}/noicon.png $imageprefix????

		$prg = & new sotf_Programme($result['id']);
		// audio files for programme
		$audioFiles = $prg->listAudioFiles('true');
		$results[$key]['audioFiles'] = array();
		$results[$key]['downloadFiles'] = array();
		foreach($audioFiles as $fileList)
		{
			if ($fileList['stream_access'] == "t") $results[$key]['audioFiles'][] = $fileList;
			if ($fileList['download_access'] == "t") $results[$key]['downloadFiles'][] = $fileList;
		}


//		$audioFiles = $prg->listAudioFiles('true');
//		for ($i=0;$i<count($audioFiles);$i++)
//		{
//			$mainAudio[$audioFiles[$i]['filename']] = $audioFiles[$i];
//		}

	}

	$retval = xmlrpc_encoder($results);
	return new xmlrpcresp($retval);
}

function putEvents($params) {
  global $config, $db, $repository;
  $events = xmlrpc_decoder($params->getParam(0));
  foreach($events as $event) {
    debug("PORTAL EVENT", $event);
    $progId = $event['prog_id'];
    if($progId) {
      if($repository->looksLikeId($progId))
	$prg = &$repository->getObject($progId);
      if(!$prg) {
	debug("Invalid prog_id arrived in portal event", $progId);
	continue;
      }
      $nodeId = $prg->getNodeId();
      //$nodeId = $repository->getNodeId($progId);
      if($nodeId != $config['nodeId']) {
        // event for remote object
        sotf_NodeObject::createForwardObject('event', $event, $progId , $nodeId);
        continue;
      }
    } 
    $repository->processPortalEvent($event);
  }
  $retval = xmlrpc_encoder(count($events));
  return new xmlrpcresp($retval);
}

stopTiming();
$page->logRequest();
debug("--------------- XML-RPC SERVER FINISHED -----------------------------------");
