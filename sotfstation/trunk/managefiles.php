<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/
	include("init.inc.php");	# include the global framework
	include("getid3/getid3.php");
	//require_once(GETID3_INCLUDEPATH.'getid3.functions.php'); // Function library
	
	//can I edit this? (this is my authorize!)
	if(($_SESSION['USER']->get("edit_station")==2) or ($_SESSION['USER']->get("auth_id") == $db->getOne("SELECT series.owner FROM programme LEFT JOIN series ON (programme.series_id = series.id) WHERE programme.id = '$_GET[id]'"))){
		$mod_flag = TRUE;
		
		//authorative data
		$smarty->assign("station_access",$mod_flag);
		$smarty->assign("edit_station",$_SESSION['USER']->get("edit_station"));
	}
	
	#############
	# uploading #
	###############################
	if($_FILES['file']){
		if(eregi("\.mp3$",$_FILES['file']['name'])){
			$dest = "audio";
		}else{
			$dest = "files";
		}
		
		move_uploaded_file($_FILES['file']['tmp_name'],PROG_DIR . $_GET['id'] . "/XBMF/" . $dest . "/" . $_FILES['file']['name']);
		header("Location: managefiles.php?id=$_GET[id]");
		exit;
	}
	
	# deleting
	if($_GET['action'] == 'delete'){
		@unlink(PROG_DIR . $_GET['id'] . "/XBMF/" . "files/" . $_GET['file']);
	}
	
	if($_GET['action'] == 'deleteaudio'){
		@unlink(PROG_DIR . $_GET['id'] . "/XBMF/" . "audio/" . $_GET['file']);
	}
	
	#####################
	# Listing to smarty #
	##### AUDIO FILES #############
	$d = dir(PROG_DIR . $_GET['id'] . "/XBMF/" . "audio");	//open directory
		
	//loop through entries
	while (false !== ($entry = $d->read())) {
		if($entry != '.' and $entry != '..'){
			$file['name'] 	= $entry;
			$mp3info = GetAllFileinfo(PROG_DIR . $_GET['id'] . "/XBMF/" . "audio/" . $entry);
			$file['length'] = $mp3info['playtime_string'];
			$file['channelmode'] = $mp3info['audio']['channelmode'];
			$file['bitrate'] = $mp3info['bitrate'] / 1000;
			$file['samplerate'] = $mp3info['audio']['sample_rate'] / 1000;
			$audiofiles[] 	= $file;
		}
	}
	$d->close();
	$smarty->assign("path","progs/" . $_GET['id'] . "/XBMF/" . "audio/");
	$smarty->assign("audiofiles",$audiofiles);
	
	##### OTHER FILES
	$d = dir(PROG_DIR . $_GET['id'] . "/XBMF/" . "files");	//open directory
		
	//loop through entries
	while (false !== ($entry = $d->read())) {
		if($entry != '.' and $entry != '..'){
			$files[] = $entry;
		}
	}
	$d->close();
	$smarty->assign("files",$files);
	
	$smarty->assign("id",$_GET['id']);
	
	//page output :)	
	pageFinishPopup('managefiles.htm');							# enter the desired template name as a parameter
?>