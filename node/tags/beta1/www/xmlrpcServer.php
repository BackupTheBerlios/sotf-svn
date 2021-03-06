<?php
/*  -*- tab-width: 3; indent-tabs-mode: 1; -*-
 * $Id$
 *
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: Andr�s Micsik, M�t� Pataki, Tam�s D�ri 
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
$map['portal.query'] = array('function' => 'getQueryResults');
$map['portal.playlist'] = array('function' => 'getProgrammes');
$map['sotf.cv.listnames'] = array('function' => 'cvListNames');
$map['sotf.cv.get'] = array('function' => 'cvGet');
//$map['sotf.allIds'] = array('function' => 'allIds');
//$map['sotf.getById'] = array('function' => 'getById');
//$map['sotf.ref'] = array('function' => 'itemReference');
//$map['sotf.comment'] = array('function' => 'itemComment');
//$map['sotf.nodeForID'] = array('function' => 'getNodeForID');

new xmlrpc_server($map);

function checkAccess($neighbour) {
  $url = $neighbour->getUrl();
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

function cvListNames($params) {
  global $repository;
  debug("incoming XML-RPC request: sotf.cv.listnames");
  // TODO: check access
  $retval = $repository->getCVocabularyNames();
  $retval = xmlrpc_encode($retval);
  return new xmlrpcresp($retval);
}

function cvGet($params) {
  global $repository;
  debug("incoming XML-RPC request: sotf.cv.get");
  // TODO: check access
  $type = xmlrpc_decode($params->getParam(0));
  $name = xmlrpc_decode($params->getParam(1));
  $lang = xmlrpc_decode($params->getParam(2));
  $retval = $repository->getCVocabulary($type, $name, $lang);
  $retval = xmlrpc_encode($retval);
  return new xmlrpcresp($retval);
}

function syncResp($params) {
  debug("incoming SYNC request");
  $chunkInfo = xmlrpc_decode($params->getParam(0));
  $nodeData = xmlrpc_decode($params->getParam(1));
  $objects = xmlrpc_decode($params->getParam(2));
  $neighbour = sotf_Neighbour::getById($nodeData['node_id']);
  if(!$neighbour) {
    logError("No access: you are not an allowed neighbour node!");
    return new xmlrpcresp(0, XMLRPC_ERR_NO_ACCESS, "No access: you are not an allowed neighbour node!");
  }
  $msg = checkAccess($neighbour);
  if($msg) {
    logError($msg);
    return new xmlrpcresp(0, XMLRPC_ERR_NO_ACCESS, "No access: $msg!");
  }
  $retval = $neighbour->syncResponse($chunkInfo, $nodeData, $objects);
  // send response
  $retval = xmlrpc_encode($retval);
  return new xmlrpcresp($retval);
}


function getQueryResults($params)
{
	global $config, $db;
	$query = xmlrpc_decode($params->getParam(0));

	$advsearch = new sotf_AdvSearch();	//create new search object object with this array
	$SQLquery = $advsearch->Deserialize($query);		//deserialize the content of the hidden field
	$query = $advsearch->GetSQLCommand();
	$results = $db->getAll($query." LIMIT 30 OFFSET 0");
	foreach($results as $key => $result)
	{
		$icon = sotf_Blob::cacheIcon($result['id']);
		$results[$key]['icon'] = $config['cacheUrl']."/".$result['id'].".png";
		//TODO if no icon {$IMAGEDIR}/noicon.png $imageprefix????
	}
	$retval = xmlrpc_encode($results);
	return new xmlrpcresp($retval);
}

function getProgrammes($params)
{
	global $config, $db;
	$prglist = xmlrpc_decode($params->getParam(0));

	$query="SELECT programmes.* FROM (";
	$query.=" SELECT sotf_programmes.*, sotf_stations.name as station, sotf_series.title as seriestitle, sotf_series.description as seriesdescription, sotf_prog_rating.rating_value as rating FROM sotf_programmes";
	$query.=" LEFT JOIN sotf_stations ON sotf_programmes.station_id = sotf_stations.id";
	$query.=" LEFT JOIN sotf_series ON sotf_programmes.series_id = sotf_series.id";
	$query.=" LEFT JOIN sotf_prog_rating ON sotf_programmes.id = sotf_prog_rating.id";
	$query.=") as programmes WHERE published = 't'";

	$results = array();

	foreach($prglist as $prg)
	{
//		debug("------------>".$prg."<------------------");
//		debug("------------>".$query." AND id = '$prg'<------------------");
		$results[] = $db->getRow($query." AND id = '$prg'");
	}

	foreach($results as $key => $result)
	{
//		debug("------------>".$result['id']."<------------------");
		$icon = sotf_Blob::cacheIcon($result['id']);
		$results[$key]['icon'] = $config['cacheUrl']."/".$result['id'].".png";
		//TODO if no icon {$IMAGEDIR}/noicon.png $imageprefix????
	}
	$retval = xmlrpc_encode($results);
	return new xmlrpcresp($retval);
}

stopTiming();
$page->logRequest();
debug("--------------- XML-RPC SERVER FINISHED -----------------------------------");
