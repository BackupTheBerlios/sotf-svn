<?php

//TODO: only localhost can connect!

require("config.inc.php");

ini_set("error_log", $config['logFile']);
ini_set("log_errors", true);
//error_reporting (E_ALL ^ E_NOTICE);

$mystreamCmd = str_replace('__PLAYLIST__', $_GET['pl'] , $config['streamCmd']);
$mystreamCmd = str_replace('__NAME__', $_GET['n'], $mystreamCmd);
$bitrate = $_GET['br'];
$mystreamCmd = str_replace('__BITRATE__', $bitrate, $mystreamCmd);
$kbrate = round($bitrate/100);
$mystreamCmd = str_replace('__KILOBITRATE__', $kbrate, $mystreamCmd);

if($config['debug'])
  error_log("starting stream: $mystreamCmd",0);
		
exec($mystreamCmd);

if($config['debug'])
  error_log("stream started",0);

?>