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
	
	//create calendar
	$myCal = new calendar($_GET['date']);
	$myCal->select($_GET['date']);
	$smarty->assign('calendar',$myCal->show('inside.php'));
	
	$myDay = new dayView();
	$myDay->addBlock(1,'11:00','13:30','Da Bomb of da bombing clan');
	$myDay->addBlock(2,'12:40','14:30','Asda',2,'Alex','pp');
	$myDay->addBlock(3,'9:40','10:30','Great Opening',3,'Da DeeJey');
	$myDay->addBlock('aa','05:00','08:00','Boot The Booth',1,'Alex','na');
	$smarty->assign('day',$myDay->show());
	
	//create help message
	//$myHelp = new helpBox(1,'98%');							# this will fetch a help message from the database and output it
																								# in the template (if allowed to do so)
																							
	//page output :)	
	pageFinish('inside.htm');											# enter the desired template name as a parameter
?>