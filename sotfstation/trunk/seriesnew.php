<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/
	
	/**
	* Changes made to RPC Protocol
	**********/

	/************************
	* SeriesNew - create a new series
	************************/
	include("init.inc.php");							# include the global framwork
	include("functions/dayfuncs.inc.php");				# date manipulation functions
	$myNav->add($SECTION[SERIES],'inside.php');			# add entry to Navigation Bar Stack
	$myNav->add($SECTION[ADDSER],'usersnew.php');		# add entry to Navigation Bar Stack
	authorize('edit_station');							# check access rights

	
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
			
			
			//okay, lets do magic
			//if it is a 14 day period, no need to loop though all days
			if($_POST['series_period'] == 7){

				for($x=0;$x<=$totalDays;$x=$x+14){
					$thisDay = $startDay + $x*$step;			# current LOOPED day's timestamp
					$weekDay = date("w",$thisDay);				# number of the day in the week
				
					$dayData = getDayData($thisDay);

					//loop through selected days
					foreach($days as $day => $value){
						//sunday correction
						if($day == 0){
							$day = 7;
						}
						
						//if day has been selected
						if($value){
							$toInsert = $thisDay + 24*60*60*($day-$weekDay);
							
							//echo "will insert: " . date("d/m/Y",$toInsert) . "<br>";
							
							
							//if to be inserted is not past the end date
							if($toInsert < $endDay){
								insert($toInsert, $seriesID);
							}else{
								break; //quit the loop
							}
							
						}
					}
				}
			
			}else{
				for($x=0;$x<=$totalDays;$x++){
			
					$thisDay = $startDay + $x*$step;			# current LOOPED day's timestamp
					$weekDay = date("w",$thisDay);				# number of the day in the week
				
					$dayData = getDayData($thisDay);
				
					switch($_POST['series_period']){
					//every 1st occurence in a month
					case 1:	{
								if($days[$weekDay]==1 and $dayData['occurence']==1){			# if this date has been validated, use it!
									insert($thisDay, $seriesID);
								}
								break;
							}
					//every 2nd occurence in a month
					case 2:	{
								if($days[$weekDay]==1 and $dayData['occurence']==2){			# if this date has been validated, use it!
									insert($thisDay, $seriesID);
								}
								break;
							}
					//every 3rd occurence in a month
					case 3:	{
								if($days[$weekDay]==1 and $dayData['occurence']==3){			# if this date has been validated, use it!
									insert($thisDay, $seriesID);
								}
								break;
							}
					//every 4th occurence in a month
					case 4:	{
								if($days[$weekDay]==1 and $dayData['occurence']==4){			# if this date has been validated, use it!
									insert($thisDay, $seriesID);
								}
								break;
							}
					//every last occurence in a month
					case 5:	{
								if($days[$weekDay]==1 and $dayData['last']==true){				# if this date has been validated, use it!
									insert($thisDay, $seriesID);
								}
								break;
							}
					//every week
					case 6: {
								if($days[$weekDay]==1){											# if this date has been validated, use it!
									insert($thisDay, $seriesID);
								}
								break;
							}		
					
					//every first full week
					case 8: {
								if($days[$weekDay]==1 and $dayData['fullWeek']==1){				# if this date has been validated, use it!
									insert($thisDay, $seriesID);
								}
								break;
							}
					//every second full week
					case 9: {
								if($days[$weekDay]==1 and $dayData['fullWeek']==2){				# if this date has been validated, use it!
									insert($thisDay, $seriesID);
								}
								break;
							}
					//every third full week
					case 10:{
								if($days[$weekDay]==1 and $dayData['fullWeek']==3){				# if this date has been validated, use it!
									insert($thisDay, $seriesID);
								}
								break;
							}
					//every fourth full week
					case 11:{
								if($days[$weekDay]==1 and $dayData['fullWeek']==4){				# if this date has been validated, use it!
									insert($thisDay, $seriesID);
								}
								break;
							}
					}
				}
			}

			//redirect to confirm page
			header("Location: confirm.php?action=3&next=week");
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
							"series_period" => array(
														1=>"Every 1st Occurence",
														2=>"Every 2nd Occurence",
														3=>"Every 3rd Occurence",
														4=>"Every 4th Occurence",
														5=>"Every Last Occurence",
														6=>"Every Week",
														7=>"Every 14 days",
														8=>"Every First Full Week",
														9=>"Every Second Full Week",
														10=>"Every Third Full Week",
														11=>"Every Fourth Full Week"),
														
							"series_active" => array('t'=>$STRING['ACTIVE'],'f'=>$STRING['NOTACTIVE']),
							"series_owner" => $db->getAssoc("SELECT auth_id, name || ': '::\"varchar\" || role AS name FROM user_map WHERE access_id < 4 ORDER BY name")
						));
												
	//page output :)	
	pageFinish('seriesnew.htm');									# enter the desired template name as a parameter
?>