<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/

	/************************
	* Inside - the first page that the anyone sees after login
	*----------------------------------------
	* This page will display the whole station schedule including the
	* past and the future
	************************/
	include("init.inc.php");											# include the global framwork
	include("classes/calendar.class.php");				# calendar handler
	include("classes/dayview.class.php");					# current day calendar handler
	$myNav->add($SECTION[INSIDE],'index.php');		# add entry to Navigation Bar Stack
	
	//fix missing date (if any)
	if(!$_GET['date']){
		$_GET['date'] = date("d-m-Y");
	}
	
	//create calendar
	$myCal = new calendar($_GET['date']);					# create new calendar object (the month overview thing)
	$myCal->select($_GET['date']);								# selects the selected day (default: today)
	$myDay = new dayView();												# create the new day representer
	
	//mark full calendar days
	//process limits
	$myDate = explode("-",$_GET['date']);
	$start = $myDate[2] . "-" . $myDate[1] . "-1 00:00:00";
	$end = $myDate[2] . "-" . $myDate[1] . "-" . date("t",mktime(0,0,1,$myDate[1],1,$myDate[2])) . " 23:59:59";
	
	//mark all the days that have content associated with them
	$myCal->setLinks($db->getCol("SELECT DISTINCT EXTRACT(DAY FROM intime) AS day FROM programme WHERE intime > '$start' AND intime < '$end'"));
	
	//show calendar
	$smarty->assign('calendar',$myCal->show('inside.php'));	
	
	//#################################### TODAY'S SHOWS #####################################
	//get today's programms ;)
	//process limits
	$start = $myDate[2] . "-" . $myDate[1] . "-" . $myDate[0] . " 00:00:00";
	$end = $myDate[2] . "-" . $myDate[1] . "-" . $myDate[0] . " 23:59:59";
	
	//run the query
	$todays_shows = $db->getAll("SELECT 
														series.owner AS series_owner, 
														series.title AS series_title,
														programme.id AS prog_id,
														programme.title AS prog_title,
														EXTRACT(EPOCH FROM programme.intime) AS intime,
														EXTRACT(EPOCH FROM programme.outtime) AS outtime,
														programme.special AS special,
														user_map.name AS owner_name FROM
														series 
														LEFT JOIN programme ON (series.id = programme.series_id)
														LEFT JOIN user_map ON (series.owner = user_map.auth_id)
														WHERE programme.intime > '$start' AND programme.intime < '$end'
											 ",DB_FETCHMODE_ASSOC);
	
	//process the resultset									 
	while(list($key,$val) = each($todays_shows)){
		if(empty($val['prog_title'])){									# if no programme title has been specifid, take the series title
			$val['prog_title'] = $val['series_title'];
		}
		
		//add calendar blocks
		$myDay->addBlock($val['prog_id'],date("H:i",$val['intime']),date("H:i",$val['outtime']),$val['prog_title'],$val['series_owner'],$val['owner_name'],$val['special']);
	}
	
	//output to smarty
	$smarty->assign('day',$myDay->show());
	//#################################### END TODAY'S SHOWS ######################################
	
	//create help message
	//$myHelp = new helpBox(1,'98%');							# this will fetch a help message from the database and output it
																								# in the template (if allowed to do so)
																							
	//page output :)	
	pageFinish('inside.htm');											# enter the desired template name as a parameter
	
	/*
	* Sometimes I hate PHP, Really
	* 															- the coder -
	**/
	
	/*
	* There are times, when your eyes start falling out and you wish for the screen to dissolve
	* in liquid plasma. @Long live da komputa@ geeks say, is this piece of metal my god, or my
	* fiercest enemy... I shall not stand...
	* 															- the coder - 
	**/
?>