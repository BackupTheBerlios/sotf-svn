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
		
		//check SOTF META DATA
		if(!$myError->checkLength($_POST['keywords'],2)){
			$myError->add($ERR[21]);
		}
		
		if(!$myError->checkLength($_POST['programme_desc'],2)){
			$myError->add($ERR[22]);
		}
		
		if(mktime(0,0,1,$_POST['dcrMonth'],$_POST['dcrDay'],$_POST['dcrYear']) > NOW){
			$myError->add($ERR[24]);
		}
		
		if(mktime(0,0,1,$_POST['disMonth'],$_POST['disDay'],$_POST['disYear']) > NOW){
			$myError->add($ERR[25]);
		}
		
		if(!$myError->checkLength($_POST['rights'])){
			$myError->add($ERR[23]);
		}
		
		########## end errors check ##########
		
		//no errorz?
		if($myError->getLength()==0){
			//add database entries
			$db->query("INSERT INTO series(owner,title,description) VALUES('$_POST[series_owner]','$_POST[series_title]','$_POST[series_description]')");
			
			$db->query("INSERT INTO programme(series_id,intime,outtime,title,special,alt_title,keywords,description,contributors,created,issued,topic,genre,lang,rights) VALUES(
																				(SELECT max(id) FROM series),
																				'$_POST[sdYear]-$_POST[sdMonth]-$_POST[sdDay] $_POST[sdHour]:$_POST[sdMinute]:00',
																				'$_POST[edYear]-$_POST[edMonth]-$_POST[edDay] $_POST[edHour]:$_POST[edMinute]:00',
																				'$_POST[programme_title]',
																				'$_POST[special_needs]',
																				'$_POST[alt_title]',
																				'$_POST[keywords]',
																				'$_POST[programme_desc]',
																				'$_POST[contrib]',
																				'$_POST[dcrYear]-$_POST[dcrMonth]-$_POST[dcrDay]',
																				'$_POST[disYear]-$_POST[disMonth]-$_POST[disDay]',
																				'$_POST[sotf_topic]',
																				'$_POST[sotf_genre]',
																				'$_POST[sotf_lang]',
																				'$_POST[rights]')");
			
			//redirect to confirm page
			header("Location: confirm.php?action=2&next=inside");
		}else{		# errorz :(
			//assign smarty default data
			$smarty->assign(array(
														"submit_series_title"				=>$_POST['series_title'],
														"submit_series_description"	=>$_POST['series_description'],
														"submit_programme_title"		=>$_POST['programme_title'],
														"submit_special_needs"			=>$_POST['special_needs'],
														"submit_series_owner"				=>$_POST['series_owner'],
														"stime"											=>mktime($_POST['sdHour'],$_POST['sdMinute'],1,$_POST['sdMonth'],$_POST['sdDay'],$_POST['sdYear']),
														"time"											=>mktime($_POST['edHour'],$_POST['edMinute'],1,$_POST['edMonth'],$_POST['edDay'],$_POST['edYear']),
														"submit_alt_title"					=>$_POST['alt_title'],
														"submit_keywords"						=>$_POST['keywords'],
														"submit_programme_desc"			=>$_POST['programme_desc'],
														"submit_contrib"						=>$_POST['contrib'],
														"dcrtime"										=>mktime(0,0,1,$_POST['dcrMonth'],$_POST['dcrDay'],$_POST['dcrYear']),
														"distime"										=>mktime(0,0,1,$_POST['disMonth'],$_POST['disDay'],$_POST['disYear']),
														"submit_sotf_topic"					=>$_POST['sotf_topic'],
														"submit_sotf_genre"					=>$_POST['sotf_genre'],
														"submit_sotf_lang"					=>$_POST['sotf_lang'],
														"submit_rights"							=>$_POST['rights']
														));
		}
	}else{
		//assign default timestamps
		$start_time = ceil(time() / 900) * 900;		# round time up to the closest quarter of an hour
		$smarty->assign("stime",$start_time);
		$smarty->assign("time",$start_time + 60*60);
		$smarty->assign("submit_series_owner",$_SESSION['USER']->get("auth_id"));
		$smarty->assign("submit_sotf_lang_default","eng");
	}
	
	//############### GET DATA FROM FILES ##############################################
	include('common/getdata.inc.php');
	
	//assign default data to drop down boxes
	$smarty->assign(array(
													"special_needs" => array(""=>$STRING['NONE'],"na"=>$STRING['NA'],"pp"=>$STRING['PP']),
													"series_owner" => $db->getAssoc("SELECT auth_id, name || ': '::\"varchar\" || role AS name FROM user_map WHERE access_id < 4 ORDER BY name"),
													"sotf_lang"	=> $langs,
													"sotf_genres"	=> $mygenres,
													"sotf_topics"	=> $mytopics
												));
												
	//page output :)	
	pageFinish('programmenew.htm');									# enter the desired template name as a parameter
?>