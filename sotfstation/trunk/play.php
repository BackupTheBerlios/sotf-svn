<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/
	$_GET['file'] = stripslashes(urldecode($_GET['file']));
	
	$path = CONF_SERVER_ROOT . PROG_URL . $_GET['id'] . "/XBMF/" . "audio/" . $_GET['file'];
	
	header("Content-type: audio/mpeg");
	header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	header("Content-Disposition: attachment; filename=" . "playlist_" . $_GET[id] . ".m3u");
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	
	echo $path;
	exit;
?>