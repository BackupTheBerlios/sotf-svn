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
	$_GET['file'] = stripslashes($_GET['file']);
	
	
	################## FETCH FILE INFO ###################################
	//get info
	$mp3info = GetAllFileinfo(PROG_DIR . $_GET['id'] . "/XBMF/" . "audio/" . $_GET['file']);
	$file['name'] 	= $_GET['file'];
	$file['length'] = $mp3info['playtime_string'];
	$file['channelmode'] = $mp3info['audio']['channelmode'];
	$file['bitrate'] = $mp3info['bitrate'] / 1000;
	$file['samplerate'] = $mp3info['audio']['sample_rate'] / 1000;
	
	
	################## PROCESS CUT ########################################
	if($_POST['Submit']){
		$start = $_POST['smin'] * 60 + $_POST['ssec'];
		$end = $_POST['emin'] * 60 + $_POST['esec'];
		
		if($start > $end or $start > $mp3info['playtime_seconds']){
			$smarty->assign("error",true);
		}else{
			//echo "mp3splt " . PROG_DIR . $_GET['id'] . "/audio/" . $_GET['file'] . " " . $_POST['smin'] . "." . $_POST['ssec'] . " " . $_POST['emin'] . "." . $_POST['esec'] . " " . PROG_DIR . $_GET['id'] . "/audio/asda.mp3";
			$newFile = uniqid("audio_") . ".mp3";
			exec("mp3splt " . PROG_DIR . $_GET['id'] . "/XBMF/audio/" . $_GET['file'] . " " . $_POST['smin'] . "." . $_POST['ssec'] . " " . $_POST['emin'] . "." . $_POST['esec'] . " " . PROG_DIR . $_GET['id'] . "/XBMF/audio/" . $newFile);
			$smarty->assign("confirm",true);
			$smarty->assign("new_file",$newFile);
		}
		
		$smarty->assign("smin",$_POST['smin']);
		$smarty->assign("ssec",$_POST['ssec']);
		$smarty->assign("emin",$_POST['emin']);
		$smarty->assign("esec",$_POST['esec']);
	}
	
	
	################## SHOW DATA ##########################################
	$smarty->assign("file",$file);
	
	//seconds drop down
	for($i=0;$i<60;$i++){
		$seconds[] = $i;
	}
	$smarty->assign("seconds",$seconds);

	//minutes drop down
	$tminutes = floor($mp3info['playtime_seconds'] / 60);
	for($i=0;$i<=$tminutes;$i++){
		$minutes[] = $i;
	}
	$smarty->assign("minutes",$minutes);
	
	$smarty->assign("path",PROG_DIR . $_GET['id'] . "/XBMF/" . "audio/");
	$smarty->assign("id",$_GET['id']);
	
	//page output :)	
	pageFinishPopup('cutaudio.htm');	# enter the desired template name as a parameter
?>