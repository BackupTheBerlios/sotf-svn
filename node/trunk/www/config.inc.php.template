<?php

//////////////////////////////////////////////////////////////////////////
// SQL SETUP --------------------------------------------
//////////////////////////////////////////////////////////////////////////

// the sql connection URL
$nodeDbUser = 'micsik';
$nodeDbHost = 'samson';
$nodeDbPort = '5432';
$nodeDbPasswd = '';
$nodeDbName = 'node';

$userDbUser = 'micsik';
$userDbHost = 'samson';
$userDbPort = '5432';
$userDbPasswd = '';
$userDbName = 'sadm';

//////////////////////////////////////////////////////////////////////////
// MAIL --------------------------------------------
//////////////////////////////////////////////////////////////////////////

// e-mails sent by the code will have this address as From:
$mailFromAddress = "micsik@dsd.sztaki.hu";

//////////////////////////////////////////////////////////////////////////
// NODE SETUP --------------------------------------------
//////////////////////////////////////////////////////////////////////////

// id of this node in the node network
$nodeId = "1";

// the short name of this node in the network
$nodeName = "HU1";

// the description of this node
$nodeDesc = "This is the first Nigerian node.";

// list of node ids with repository URLs
$neighbourNodes = array( "HU2" => 'http://sotf.dsd.sztaki.hu'
			);

//////////////////////////////////////////////////////////////////////////
// FILE LOCATIONS  --------------------------------------------
//////////////////////////////////////////////////////////////////////////

// the directory where stations and radio shows will be archived
$repositoryDir = 'C:/sotf/node/repository';

// the directory where stations and radio shows will be archived
$musicDir = 'C:/sotf/node/music';

// the directory under which users have FTP or other access to send files to
$userDirs = 'C:/sotf/node/users';

// the FTP URL for user directories (defined above)
$userFTP = 'ftp://sotf2.dsd.sztaki.hu:8989/';

// $basedir -> Complete filesystem path
$basedir = 'C:/sotf/node';

// where PEAR files are located
$peardir = 'C:/Program Files/php422/pear';

// where Smarty files are located
$smartydir = 'C:/sotf/helpers/smarty2.3';

// where getid3 files are located
$getid3dir = 'C:/sotf/helpers/getid3';

// where ImageMagick is installed
$magickDir = 'C:/Program Files/ImageMagick';

// where eZ xml files are located
//$ezxmldir = '/ezxml';

// Path to the class files
$classdir = $basedir . '/code/classes';

// path to PHP scripts and images
$wwwdir = $basedir . '/www';

// The URL prefix for code/www subdir
$localPrefix = '/node/www';

//////////////////////////////////////////////////////////////////////////
// STREAMING --------------------------------------------
//////////////////////////////////////////////////////////////////////////

// Parameters for connection to icecast streaming server
$iceServer = "sotf.dsd.sztaki.hu";
$icePort = "8000";
$encoderPassword = "radjo";

// The command to start streaming __XX__ style tokens are replaced with the actual value
$streamCmd = "C:/Perl/bin/perl $basedir/code/contrib/iceplay -b __BITRATE__ -s $iceServer -P $icePort -p $encoderPassword -l __PLAYLIST__ -n __NAME__ ";
##>>logs/play.log 2>&1 &";  

// The server will not start more streams than this
$maxNumberOfStreams = 30;

//////////////////////////////////////////////////////////////////////////
// DEBUGGING, LOGGING & ERROR HANDLING -----------------------------------
//////////////////////////////////////////////////////////////////////////

$logFile = "$basedir/code/logs/log";

$debug = true;		// here you can set the default on, false for off
$debug_type = 'later';	// 'now' for output to browser
			// 'log' for output to the admin log
$sqlDebug = true;	// print all executed SQL statements into log

//////////////////////////////////////////////////////////////////////////
// LANGUAGE SETUP  --------------------------------------------
//////////////////////////////////////////////////////////////////////////

// selectable languages for user interface
$outputLanguages = array("en");
#$outputLanguages = array("en", "de");

$defaultLanguage = "en";

$languages = array("en", "de", "hu", "it", "es", "cz", "fr", "ro", "ru");

//////////////////////////////////////////////////////////////////////////
// AUDIO OPTIONS ------------------------------------------------------
//////////////////////////////////////////////////////////////////////////

/**
* Required formats.
*
* Array of associative arrays contains information about the audio format.
* @package	StreamOnTheFly
* @variable	array	$audioFormats
*/
$audioFormats = array(
					array(
						'format' => 'mp3',
						'bitrate' => '24',
						'channels' => '1',
						'samplerate' => '22050'),
					array(
						'format' => 'mp3',
						'bitrate' => '128',
						'channels' => '2',
						'samplerate' => '44100'),
					array(
						'format' => 'ogg',
						'bitrate' => '64',
						'channels' => '2',
						'samplerate' => '22050'),
				);

$iconWidth = 100;
$iconHeight = 100;

$lame = 'C:/sotf/helpers/lame';

//////////////////////////////////////////////////////////////////////////
// MISC OPTIONS ------------------------------------------------------
//////////////////////////////////////////////////////////////////////////

// known MIME types for uploaded documents
$mimetypes = array(
				'doc'=>'application/msword',
				'gif'=>'image/gif',
				'htm'=>'text/html',
				'html'=>'text/html',
				'jpg'=>'image/jpeg',
				'mp3'=>'audio/mp3',
				'm3u'=>'audio/x-mpeg',
				'pdf'=>'application/pdf',
				'ps'=>'application/postscript',
				'txt'=>'text/plain',
				'xls'=>'application/vnd.ms-excel'
				);

?>
