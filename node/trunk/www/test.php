<?php

require("init.inc.php");

require_once($config['classdir'] . "/rpc_Utils.class.php");


$rpc = new rpc_Utils;
$rpc->debug = true;

$localNode = sotf_Node::getLocalNode();
$localNodeData = $localNode->getAll();
$localNodeData['url'] = $config['rootUrl'];

$chunkInfo = array('this_chunk' => 1,
                   'node' => $localNodeData,
                   'objects_remaining' => 0,
                   );

$blid = '005bl15';

$bl = $repository->getObject($blid);
$bl->update();

$obj = $bl->internalData;
$obj['data'] = $bl->data;
$obj['data']['data'] = 'A%0a';
$objects = array($obj);

//$data = $db->getRow("SELECT * FROM $tablename WHERE id = '$blid'");

$objs = array($chunkInfo, $objects);
$response = $rpc->call('http://sotf2.dsd.sztaki.hu/node2/www/xmlrpcServer.php', 'sotf.sync', $objs);

/*
print "<pre>";
$a = NULL;
if(is_null($a)) print "\nNULL IS_NULL";
if($a === NULL) print "\nNULL === NULL";
if($a == NULL) print "\nNULL == NULL";

$a = 0;
if(is_null($a)) print "\n0 IS_NULL";
if($a === NULL) print "\n0 === NULL";
if($a == NULL) print "\n0 == NULL";

print "</pre>";
*/

/*
$ConnId = pg_connect ("host=localhost port=5432 dbname=node user=micsik password=");
$ResId = pg_query("SELECT * FROM sotf_nodes");
$res = pg_fetch_row ($ResId);
pg_close ($ConnId);

print "<pre>";
print_r($res);

if($res[3]===NULL)
     print "\nNULL!!!";
print "</pre>";
*/

/*
$rpc = new rpc_Utils;
$rpc->debug = true;
//$response = $rpc->call($config['rootUrl'] . "/xmlrpcServer.php", 'sotf.cv.listnames', '');
$response = $rpc->call($config['rootUrl'] . "/xmlrpcServer.php", 'sotf.cv.get', array('topics',1,'fr'));
print "<pre>";
print_r($response);
print "</pre>";
*/

/*
$tree = $repository->getTree(1, 'en');

print "<pre>";
print_r($tree);
print "</pre>";

exit;
*/

/*
require_once($config['classdir'] . "/rpc_Utils.class.php");

$rpc = new rpc_Utils;
$rpc->debug = true;
$response = $rpc->call($config['tamburineURL'], 'version', '');
*/


/*
require_once($config['classdir'] . '/unpackXML.class.php');

$myPack = new unpackXML($config['basedir'] . "/metasample.txt");	

if(!$myPack->error){		//if the file has been found
  $metadata = $myPack->process();
}
		

			echo "<pre>";
			print_r($metadata);
			echo "</pre>";
	
    //dump($metadata, "METADATA");

exit;

sotf_Programme::importXBMF($config['xbmfInDir'] . "/test.xbmf");


exit;
*/

//echo "<br>getenv:" . getenv('REMOTE_ADDR');
//echo "<br>_SERVER:" . $_SERVER['REMOTE_ADDR'];

//$res = $userdb->getOne("SELECT auth_id FROM authenticate WHERE username = 'akazcs'");
//echo "'$res'";

#$page->send();

///$repository->updateTopicCounts();

/*
$mainContent = false;
echo ($mainContent ? 't' : 'f');

if(is_array(NULL))
     print "ARRAY IS NULL";

$k = '';
$k= 0;
if(empty($k))
     print("empty");
     else
     print("not");
*/

/*
$series = new sotf_Series('001se1');
$iconfile = 'C:/sotf/node/www/tmp/1043930362.png';

$fp = fopen($iconfile,'rb');
$data = fread($fp,filesize($iconfile));
fclose($fp);

//dump($data, 'data');

//dump($db->unescape_bytea($db->escape_bytea($data), 'escaped'));
dump($db->escape_bytea($data), 'escaped');
//echo pg_host(1);

//dump(pg_escape_bytea($data), 'escaped2');


//exit;

sotf_Blob::saveBlob($series->id, 'icon', $data);

dump(sotf_Blob::findBlob($series->id, 'icon'), 'icon');

*/

/*
$obj = new sotf_Blob();
$obj->set('object_id', $series->id);
$obj->set('name', 'icon');
$obj->find();
$obj->set('data', $data);
*/

//dump($obj->data['data'], 'objbol1');

//$obj->update();

//dump($obj->data['data'], 'objbol2');

//dump($obj->get('data'), 'data2');

//$prog->setBlob('icon', $prog->get('icon'));

//echo "Icon saved";

/*
$ids = $db->getCol("select id from sotf_programmes");
while(list(, $id) = each($ids)) {
  $obj = new sotf_Programme($id);
  $icon = $obj->getBlob('icon');
  $obj->saveBlob('icon', $icon);
  echo "<br>icon saved for $id";
}

$ids = $db->getCol("select id from sotf_stations");
while(list(, $id) = each($ids)) {
  $obj = new sotf_Station($id);
  $icon = $obj->getBlob('icon');
  $obj->saveBlob('icon', $icon);
  echo "<br>icon saved for $id";
}

$ids = $db->getCol("select id from sotf_series");
while(list(, $id) = each($ids)) {
  $obj = new sotf_Series($id);
  $icon = $obj->getBlob('icon');
  $obj->saveBlob('icon', $icon);
  echo "<br>icon saved for $id";
}

$ids = $db->getCol("select id from sotf_contacts");
while(list(, $id) = each($ids)) {
  $obj = new sotf_Contact($id);
  $icon = $obj->getBlob('icon');
  $obj->saveBlob('icon', $icon);
  echo "<br>icon saved for $id";
}

*/

?>


