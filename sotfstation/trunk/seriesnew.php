<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/

	/************************
	* SeriesNew - create a new series
	************************/
	include("init.inc.php");												# include the global framwork
	include("functions/dayfuncs.inc.php");					# date manipulation functions
	$myNav->add($SECTION[SERIES],'inside.php');			# add entry to Navigation Bar Stack
	$myNav->add($SECTION[ADDSER],'usersnew.php');		# add entry to Navigation Bar Stack
	authorize('edit_station');											# check access rights

	
	/**
	 * insert() - insert programme into series
	 * 
	 * @param $thisDay
	 * @return 
	 */
	function insert($thisDay, $seriesID = 0){
		global $db;
		
		//check if this is a midnight show
		//figure out if times lie in the same day or not...
		if(($_POST[edHour]>$_POST[sdHour]) or (($_POST[edHour] == $_POST[sdHour]) and ($_POST[edMinute]>$_POST[sdMinute]))){			
			// if the end hour is after the start hour, then this is the same day, I presume there are no shows that take more than 24 hours
			$nextDay = $thisDay;
		}else{
			$nextDay = $thisDay + 60*60*24;
		}
		
		//call the query
		$db->query("INSERT INTO programme(series_id,intime,outtime) VALUES(
																				'$seriesID',
																				'" . date("Y-m-d",$thisDay) . " $_POST[sdHour]:$_POST[sdMinute]:00',
																				'" . date("Y-m-d",$nextDay) . " $_POST[edHour]:$_POST[edMinute]:00')");
	}
	
	###################################### POST PRODUCE ########################################################################
	//work around POSTED data
	if($_POST['Submit']){
		$_POST = clean($_POST);												# clean bad inputs
		
		########## check for errors ##########
		//check if fields filled in
		if(!$myError->checkLength($_POST['series_title'],2)){
			$myError->add($ERR[15]);
		}
		
		if(!$myError->checkLength($_POST['series_description'],2)){
			$myError->add($ERR[16]);
		}
		
		//check if enddate is greater than start date
		if(mktime($_POST['edHour'],$_POST['edMinute'],1,$_POST['edMonth'],$_POST['edDay'],$_POST['edYear']) <= mktime($_POST['sdHour'],$_POST['sdMinute'],1,$_POST['sdMonth'],$_POST['sdDay'],$_POST['sdYear'])){
			$myError->add($ERR[18]);
		}
		
		//check if end date and start date are the same day
		if(mktime(1,1,1,$_POST['edMonth'],$_POST['edDay'],$_POST['edYear']) == mktime(1,1,1,$_POST['sdMonth'],$_POST['sdDay'],$_POST['sdYear'])){
			$myError->add($ERR[19]);
		}
		
		//check if at least one day is selected
		if($_POST[mon] or $_POST[tue] or $_POST[wed] or $_POST[thu] or $_POST[fri] or $_POST[sat] or $_POST[sun]){
			//fix values
			$days = array(1=>$_POST[mon],2=>$_POST[tue],3=>$_POST[wed],4=>$_POST[thu],5=>$_POST[fri],6=>$_POST[sat],0=>$_POST[sun]);
		}else{
			$myError->add($ERR[20]);
		}
		
		########## end errors check ##########
		
		//no errorz?
		if($myError->getLength()==0){
			//add database entries
			$db->query("INSERT INTO series(owner,title,description,active) VALUES('$_POST[series_owner]','$_POST[series_title]','$_POST[series_description]','$_POST[series_active]')");
			$seriesID = $db->getOne("SELECT max(id) FROM series");
			
			###########################################################################
			//the tough part... get programm repetition patterns
			//the basic algorythm is to loop through all the times in the given timeframe
			//and check whether the show runs on THIS day, if so, make a DB entry
			//first figure out how much to loop :)
			$totalDays = round((mktime(1,1,1,$_POST['edMonth'],$_POST['edDay'],$_POST['edYear']) - mktime(1,1,1,$_POST['sdMonth'],$_POST['sdDay'],$_POST['sdYear'])) / (24 * 60 * 60));
			$startDay = mktime(1,1,1,$_POST['sdMonth'],$_POST['sdDay'],$_POST['sdYear']);
			$endDay = mktime(1,1,1,$_POST['edMonth'],$_POST['edDay'],$_POST['edYear']);
			$step = 60*60*24;
			
			
			//okay, lets fo magic
			for($x=0;$x<=$totalDays;$x++){
				$thisDay = $startDay + $x*$step;			# current LOOPED day's timestamp
				$weekDay = date("w",$thisDay);				# number of the day in the week
				
				switch($_POST['series_period']){
					//every 4st week
					case 1:	{
										if(($days[$weekDay]==1) and (getDayData($thisDay)==1)){		# if this date has been validated, use it!
											insert($thisDay, $seriesID);
										}
										break;
									}
					//every 2nd week
					case 2:	{
										if(($days[$weekDay]==1) and (getDayData($thisDay)==2)){		# if this date has been validated, use it!
											insert($thisDay, $seriesID);
										}
										break;
									}
					//every 3rd week
					case 3:	{
										if(($days[$weekDay]==1) and (getDayData($thisDay)==3)){		# if this date has been validated, use it!
											insert($thisDay, $seriesID);
										}
										break;
									}
					//every 4th week
					case 4:	{
										if(($days[$weekDay]==1) and (getDayData($thisDay)==4)){		# if this date has been validated, use it!
											insert($thisDay, $seriesID);
										}
										break;
									}
					//every last week
					case 5:	{
										if(($days[$weekDay]==1) and (isLastWeek($thisDay))){		# if this date has been validated, use it!
											insert($thisDay, $seriesID);
										}
										break;
									}
					//every week
					case 6: {
										if($days[$weekDay]==1){		# if this date has been validated, use it!
											insert($thisDay, $seriesID);
										}
										break;
									}
					
					//every even week
					case 7:	{
										if(($days[$weekDay]==1) and (even(getDayData($thisDay)))){		# if this date has been validated, use it!
											insert($thisDay, $seriesID);
										}
										break;
									}
									
					//every odd week
					case 8:	{
										if(($days[$weekDay]==1) and (odd(getDayData($thisDay)))){		# if this date has been validated, use it!
											insert($thisDay, $seriesID);
										}
										break;
									}
				}
			}

			//redirect to confirm page
			header("Location: confirm.php?action=3&next=inside");
		}else{		# errorz :(
			//assign smarty default data
			$smarty->assign(array(
														"submit_series_title"=>$_POST['series_title'],
														"submit_series_description"=>$_POST['series_description'],
														"submit_programme_title"=>$_POST['programme_title'],
														"submit_special_needs"=>$_POST['special_needs'],
														"submit_series_owner"=>$_POST['series_owner'],
														"stime"=>mktime($_POST['sdHour'],$_POST['sdMinute'],1,$_POST['sdMonth'],$_POST['sdDay'],$_POST['sdYear']),
														"time"=>mktime($_POST['edHour'],$_POST['edMinute'],1,$_POST['edMonth'],$_POST['edDay'],$_POST['edYear']),
														"mon"=>$_POST['mon'],
														"tue"=>$_POST['tue'],
														"wed"=>$_POST['wed'],
														"thu"=>$_POST['thu'],
														"fri"=>$_POST['fri'],
														"sat"=>$_POST['sat'],
														"sun"=>$_POST['sun'],
														"submit_series_period"=>$_POST['series_period'],
														"submit_series_active"=>$_POST['series_active']
														));
		}
	}else{
		//assign default timestamps
		$start_time = ceil(time() / 900) * 900;		# round time up to the closest quarter of an hour
		$smarty->assign("stime",$start_time);
		$smarty->assign("time",$start_time + 60*60 + 60*60*24*28);
		$smarty->assign("submit_series_owner",$_SESSION['USER']->get("auth_id"));
	}
	
	//assign default data to drop down boxes
	$smarty->assign(array(
													"series_period" => array(1=>"Every 1st Week",2=>"Every 2nd Week",3=>"Every 3rd Week",4=>"Every 4th Week",5=>"Every Last Week",6=>"Every Week",7=>"Every Even Week",8=>"Every Odd Week"),
													"series_active" => array('t'=>$STRING['ACTIVE'],'f'=>$STRING['NOTACTIVE']),
													"series_owner" => $db->getAssoc("SELECT auth_id, name || ': '::\"varchar\" || role AS name FROM user_map WHERE access_id < 4 ORDER BY name")
												));
												
	//page output :)	
	pageFinish('seriesnew.htm');									# enter the desired template name as a parameter
?>