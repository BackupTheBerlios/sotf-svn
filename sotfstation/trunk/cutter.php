<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/

	/************************
	* This is a cron job, that will cut whole-day audio files into smaller
	* portions according to the predefined programme and push these files
	* into corresponding directories in the sync tree.
	* 
	* As a parameter the script will receive the date that the cutter will
	* be ran on in the YYYYMMDD Format. eg
	* 
	* cutter.php?date=20030428
	* 
	* Software used for splitting:
	* 	mp3split - http://mp3splt.sourceforge.net
	* 
	* Software used for glueing:
	* 	mp3wrap - http://mp3wrap.sourceforge.net
	************************/
	include("init.inc.php");										# include the global framwork
	
	//let the script go on
	set_time_limit(6000);	//100 minutes
	
	//clean inputs
	$_GET['date'] = htmlspecialchars($_GET['date']);
	
	//process inputs
	if(empty($_GET['date'])){
		$_GET['date'] = date("Ymd",time()-60*60*24);
	}
	
	//get limits
	$stamp = strtotime($_GET['date']);
	$todayStart = date("Y-m-d 00:00:00",$stamp);
	$todayEnd = date("Y-m-d 23:59:59",$stamp);
	
	echo "<b>STARTING CUTTER FOR DATE - $todayStart -</b><br><br><code>";
		
	//get data
	$progsToday = $db->getAll("
															SELECT
																id,
																intime,
																outtime
															FROM programme WHERE intime >= '$todayStart' AND outtime <= '$todayEnd'
														");
	
	//check if recorded audio is present
	if(!file_exists(UA_DIR . $_GET['date'] . "_lo.mp3")){
		trigger_error("Uncut Audio file " . $_GET['date'] . "_lo.mp3 not found!",256);
	}
	
	//check if export dir is present
	if(!is_dir(PROG_DIR)){
		mkdir(PROG_DIR);
	}
		
	//loop through the list and cut appropriately
	foreach($progsToday as $prog){
		//check if directory is there	
		if(!is_dir(PROG_DIR . $prog[0])){
			mkdir(PROG_DIR . $prog[0], 0777);
			mkdir(PROG_DIR . $prog[0] . "/audio", 0777);
			mkdir(PROG_DIR . $prog[0] . "/files", 0777);
		}
			
		//convert intime and outtime to absolute times
		$start 	= strtotime($prog[1]) - $stamp - SPLITOFFSET;
		$end		= strtotime($prog[2]) - $stamp + SPLITOFFSET;
				
		$start	= floor($start / 60) . "." . $start%60;
		$end		= floor($end / 60) . "." . $end%60;
			
		//cut it all!!!!
		echo "<b>Splitting:</b> " . UA_DIR . $_GET['date'] . "_lo.mp3 <b>From:</b> " . $start . " <b>To:</b> " . $end . " <b>Output File:</b>" . PROG_DIR . $prog[0] . "/audio/" . uniqid("audio_") . ".mp3<br>";
		exec("mp3splt " . UA_DIR . $_GET['date'] . "_lo.mp3 " . $start . " " . $end . " " . PROG_DIR . $prog[0] . "/audio/" . uniqid("audio_") . ".mp3");
	}
		
	//now the happy case - programme that goes over midnight =)
	$midnightShow = $db->getRow("SELECT id, intime, outtime FROM programme WHERE intime <= '$todayStart' and outtime >= '$todayStart'");
	
	//the midnight show has to be prepared from 2 separate files, one from THIS day, and one from THE DAY BEFORE
	//hence we create 2 mp3 files, and then merge them into one and shift into the appropriate location
	$dayBefore = date("Ymd",$stamp - 60*60*24);
		
	//small error check
	if(!file_exists(UA_DIR . $dayBefore . "_lo.mp3") and $midnightShow){
		trigger_error("Uncut Audio file " . $dayBefore . "_lo.mp3 not found!",256);
	}
		
	//let's play
	if($midnightShow){
		//error check
		if(!is_dir(PROG_DIR . $midnightShow[0])){
			mkdir(PROG_DIR . $midnightShow[0], 0777);
			mkdir(PROG_DIR . $midnightShow[0] . "/audio", 0777);
			mkdir(PROG_DIR . $midnightShow[0] . "/files", 0777);
		}
			
		//convert intime and outtime to absolute times
		$start 	= strtotime($midnightShow[1]) - $stamp + 60*60*24 - SPLITOFFSET;
		$end		= strtotime($midnightShow[2]) - $stamp + SPLITOFFSET;
			
		$start	= floor($start / 60) . "." . $start%60;
		$end		= floor($end / 60) . "." . $end%60;
			
		$bitOne = uniqid("temp_");
		$bitTwo = uniqid("temp_");
			
		echo "<b>MIDNIGHT SHOW - Hurray</b><br>";
			
		//cut the first bit
		echo "<b>Splitting:</b> " . UA_DIR . $dayBefore . "_lo.mp3 <b>From:</b> " . $start . " <b>To:</b> 1440.0 <b>Output File:</b> " . $bitOne . ".mp3<br>";
		exec("mp3splt " . UA_DIR . $dayBefore . "_lo.mp3 " . $start . " 1440.0 " . $bitOne . ".mp3");
			
		//cut the last bit
		echo "<b>Splitting:</b> " . UA_DIR . $_GET['date'] . "_lo.mp3 <b>From:</b> 0.0 <b>To:</b> " . $end . " <b>Output File:</b> " . $bitTwo . ".mp3<br>";
		exec("mp3splt " . UA_DIR . $_GET['date'] . "_lo.mp3 0.0 " . $end . " " . $bitTwo . ".mp3");
			
		//glue bits together and shift to appropriate location
		echo "<b>Wrapping Together:</b> " . $bitOne . ".mp3 AND " . $bitTwo . ".mp3 <b>Output File:</b> " . PROG_DIR . $midnightShow[0] . "/audio/" . uniqid("audio_") . ".mp3<br>";
		exec("mp3wrap " . PROG_DIR . $midnightShow[0] . "/audio/" . uniqid("audio_") . ".mp3 " . $bitOne . ".mp3 " . $bitTwo . ".mp3");
			
		//delete bits
		echo "<b>Dropping:</b> " . $bitOne . ".mp3<br>";
		unlink($bitOne . ".mp3");
			
		echo "<b>Dropping:</b> " . $bitTwo . ".mp3<br>";
		unlink($bitTwo . ".mp3");
		
		echo "</code><br><b>CUTTER COMPLETED</b>";
	}
?>