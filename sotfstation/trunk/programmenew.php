<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/

	/************************
	* ProgrammeNew - create a new one-time programme
	*----------------------------------------
	************************/
	include("init.inc.php");												# include the global framwork
	$myNav->add($SECTION[INSIDE],'inside.php');			# add entry to Navigation Bar Stack
	$myNav->add($SECTION[ADDPROG],'usersnew.php');	# add entry to Navigation Bar Stack
	authorize('edit_station');											# check access rights
	
	//work around POSTED data
	if($_POST['Submit']){
		$_POST = clean($_POST);												# clean bad inputs
		
		//figure out if times lie in the same day or not...
		if(($_POST[edHour]>$_POST[sdHour]) or (($_POST[edHour] == $_POST[sdHour]) and ($_POST[edMinute]>$_POST[sdMinute]))){			
			// if the end hour is after the start hour, then this is the same day, I presume there are no shows that take more than 24 hours
			$_POST[edYear] = $_POST[sdYear];
			$_POST[edMonth] = $_POST[sdMonth];
			$_POST[edDay] = $_POST[sdDay];
		}else{
			//show must overlap through midnight!
			$next_day = mktime(1,1,1,$_POST['sdMonth'],$_POST['sdDay'],$_POST['sdYear']) + 60*60*24;	# create +1 day timestamp
			$_POST[edYear] = date("Y",$next_day);		# generate fake POST data ;)
			$_POST[edMonth] = date("m",$next_day);
			$_POST[edDay] = date("d",$next_day);
		}
		
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
			$myError->add($ERR[17]);
		}
		
		########## end errors check ##########
		
		//no errorz?
		if($myError->getLength()==0){
			//add database entries
			$db->query("INSERT INTO series(owner,title,description) VALUES('$_POST[series_owner]','$_POST[series_title]','$_POST[series_description]')");
			$db->query("INSERT INTO programme(series_id,intime,outtime,title,special) VALUES(
																				(SELECT max(id) FROM series),
																				'$_POST[sdYear]-$_POST[sdMonth]-$_POST[sdDay] $_POST[sdHour]:$_POST[sdMinute]:00',
																				'$_POST[edYear]-$_POST[edMonth]-$_POST[edDay] $_POST[edHour]:$_POST[edMinute]:00',
																				'$_POST[programme_title]','$_POST[special_needs]')");
			
			//redirect to confirm page
			header("Location: confirm.php?action=2&next=inside");
		}else{		# errorz :(
			//assign smarty default data
			$smarty->assign(array(
														"submit_series_title"=>$_POST['series_title'],
														"submit_series_description"=>$_POST['series_description'],
														"submit_programme_title"=>$_POST['programme_title'],
														"submit_special_needs"=>$_POST['special_needs'],
														"submit_series_owner"=>$_POST['series_owner'],
														"stime"=>mktime($_POST['sdHour'],$_POST['sdMinute'],1,$_POST['sdMonth'],$_POST['sdDay'],$_POST['sdYear']),
														"time"=>mktime($_POST['edHour'],$_POST['edMinute'],1,$_POST['edMonth'],$_POST['edDay'],$_POST['edYear'])
														));
		}
	}else{
		//assign default timestamps
		$start_time = ceil(time() / 900) * 900;		# round time up to the closest quarter of an hour
		$smarty->assign("stime",$start_time);
		$smarty->assign("time",$start_time + 60*60);
		$smarty->assign("submit_series_owner",$_SESSION['USER']->get("auth_id"));
	}
	
	//assign default data to drop down boxes
	$smarty->assign(array(
													"special_needs" => array(""=>$STRING['NONE'],"na"=>$STRING['NA'],"pp"=>$STRING['PP']),
													"series_owner" => $db->getAssoc("SELECT auth_id, name FROM user_map WHERE access_id < 4 ORDER BY name")
												));
												
	//page output :)	
	pageFinish('programmenew.htm');									# enter the desired template name as a parameter
?>