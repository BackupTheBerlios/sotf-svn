<?php

//TODO: only localhost can connect!

require("config.inc.php");

$mystreamCmd = str_replace('__PLAYLIST__', $_GET['pl'] , $config['streamCmd']);
$mystreamCmd = str_replace('__NAME__', $_GET['n'], $mystreamCmd);
$mystreamCmd = str_replace('__BITRATE__', $_GET['br'], $mystreamCmd);
		
error_log("starting stream: $mystreamCmd",0);
		
exec($mystreamCmd);

error_log("stream started",0);

?>