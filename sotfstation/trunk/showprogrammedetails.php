<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/

	/*************************
	* Show Programme Details *
	*------------------------**********************************************************
	* This is a popup that will show all the details of the desired programme					*
	* Programme owner or the station manager will be able to edit all the available		*
	* information. Other users will only be able to view it.													*
	**********************************************************************************/
	include("init.inc.php");	# include the global framework
	
	//can I edit this? (this is my authorize!)
	if(($_SESSION['USER']->get("edit_station")==2) or ($_SESSION['USER']->get("auth_id") == $db->getOne("SELECT series.owner FROM programme LEFT JOIN series ON (programme.series_id = series.id) WHERE programme.id = '$_GET[id]'"))){
		$mod_flag = TRUE;
		
		//authorative data
		$smarty->assign("station_access",$mod_flag);
		$smarty->assign("edit_station",$_SESSION['USER']->get("edit_station"));
	}
	
	####################################################################
	//process Programme Delete Call
	if(($_GET['action']=='delete') and ($mod_flag)){
		$db->query("DELETE FROM programme WHERE id = '$_GET[id]'");
		
		//close window
		$smarty->assign(array("window_destroy"=>true,"destination"=>"inside.php","get_data"=>"date=" . date("d-m-Y",$_GET['timestamp'])));
	}else if(($_GET['action']=='delete_series') and ($mod_flag)){
		//drop all programmes related to this sereis
		$db->query("DELETE FROM programme WHERE series_id = '$_GET[series_id]'");
		
		//drop the series
		$db->query("DELETE FROM series WHERE id = '$_GET[series_id]'");
		
		//close window
		$smarty->assign(array("window_destroy"=>true,"destination"=>"inside.php","get_data"=>"date=" . date("d-m-Y",$_GET['timestamp'])));
	}
	
	####################################################################################################################
	//PROCESS SUBMIT (if admin)
	if($mod_flag and $_POST['Submit']){
		//clean POST
		$_POST = clean($_POST);
		
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
		if($myError->getLength()==0){
			//run update queries
			$db->query("UPDATE series SET owner = '$_POST[series_owner]', title = '$_POST[series_title]', description = '$_POST[series_description]', active = '$_POST[series_active]' WHERE id = '$_POST[series_id]'");
			$db->query("UPDATE programme SET title = '$_POST[programme_title]', intime = '$_POST[sdYear]-$_POST[sdMonth]-$_POST[sdDay] $_POST[sdHour]:$_POST[sdMinute]:00', outtime = '$_POST[edYear]-$_POST[edMonth]-$_POST[edDay] $_POST[edHour]:$_POST[edMinute]:00', special = '$_POST[special_needs]', active = '$_POST[prog_active]' WHERE id = '$_GET[id]'");
			
			//close window and redirect
			//choose where to redirect
			if(!empty($_POST['get_stuff'])){
				$smarty->assign(array("window_destroy"=>true,"destination"=>"myseries.php","get_data"=>$_POST['get_stuff']));
			}else{
				$smarty->assign(array("window_destroy"=>true,"destination"=>"inside.php","get_data"=>"date=$_POST[sdDay]-$_POST[sdMonth]-$_POST[sdYear]"));
			}
		}else{	//there were errorz, reset data
			$smarty->assign(array(
															"prog_title"						=> 	$_POST['programme_title'],
															"prog_intimets"					=> 	mktime($_POST['sdHour'],$_POST['sdMinute'],1,$_POST['sdMonth'],$_POST['sdDay'],$_POST['sdYear']),
															"prog_outtimets"				=> 	mktime($_POST['edHour'],$_POST['edMinute'],1,$_POST['edMonth'],$_POST['edDay'],$_POST['edYear']),
															"submit_special_needs"	=> 	$_POST['special_needs'],
															"series_title"					=> 	$_POST['series_title'],
															"series_desc"						=> 	$_POST['series_description'],
															"submit_series_owner"		=> 	$_POST['series_owner'],
															"tot_progs"							=> 	$db->getOne("SELECT count(*) FROM programme WHERE series_id = '$_POST[series_id]'"),
															"progs_to_run"					=> 	$db->getOne("SELECT count(*) FROM programme WHERE series_id = '$_POST[series_id]' AND intime > '" . date("Y-m-d H:i:s") . "'"),
															"series_id"							=> 	$_POST['series_id'],
															"special_needs" 				=> 	array(""=>$STRING['NONE'],"na"=>$STRING['NA'],"pp"=>$STRING['PP']),
															"series_owner" 					=> 	$db->getAssoc("SELECT auth_id, name FROM user_map WHERE access_id < 4 ORDER BY name"),
															"series_active" 				=> 	array('t'=>$STRING['ACTIVE'],'f'=>$STRING['NOTACTIVE']),
															"prog_active" 					=> 	array('t'=>$STRING['ACTIVE'],'f'=>$STRING['NOTACTIVE']),
															"submit_series_active"	=>	$_POST['series_active'],
															"submit_prog_active"		=>	$_POST['prog_active'],
															"get_stuff"							=>	$_POST['get_stuff'],
															"programme_id" 					=>  $_GET['id']
													 )
											);
		}//end if error
		
	}else{ // no submit action has been taken
		//get all the default data
		$programme_data = $db->getRow("SELECT 
									to_char(programme.intime,'DD-MM-YYYY') AS prog_date,
									to_char(programme.intime,'HH24:MI') AS prog_intime,
									to_char(programme.outtime,'HH24:MI') AS prog_outtime,
									EXTRACT(EPOCH FROM programme.intime) AS prog_intimets,
									EXTRACT(EPOCH FROM programme.outtime) AS prog_outtimets,
									programme.title AS prog_title,
									programme.special AS prog_special,
									programme.series_id AS series_id,
									programme.active AS submit_prog_active,
									series.title AS series_title,
									series.active AS submit_series_active,
									series.description AS series_desc,
									user_map.name AS series_owner,
									user_map.auth_id AS series_owner_id
							 FROM programme 
							 LEFT JOIN series ON (programme.series_id = series.id)
							 LEFT JOIN user_map ON (series.owner = user_map.auth_id)
							 WHERE programme.id = '$_GET[id]'",DB_FETCHMODE_ASSOC);
	
		$prog_special = $programme_data['prog_special'];
		switch($programme_data['prog_special']){
			case 'na'	:{$programme_data['prog_special'] = $STRING['NA']; break;}
			case 'pp'	:{$programme_data['prog_special'] = $STRING['PP']; break;}
			default		:{$programme_data['prog_special'] = $STRING['NONE'];}
		}
		
		//fix values for non-mods
		if(empty($programme_data['prog_title']) and !$mod_flag){
			$programme_data['prog_title'] = $STRING['NONE'];
		}
		
		//if this is not a mod, then drop-down data has to be transformed to be textual data
		if(!$mod_flag){
			//user id is not needed any more
			$programme_data['series_owner_id'] = $programme_data['series_owner'];
			
			//nor are the TRUE and FALSE boolean values
			if($programme_data['submit_series_active']=='t'){
				$programme_data['submit_series_active'] = $STRING['ACTIVE'];
				$programme_data['submit_series_active_flag'] = 't';							# flag for smarty template
			}else{
				$programme_data['submit_series_active'] = $STRING['NOTACTIVE'];
			}
			
			if($programme_data['submit_prog_active']=='t'){
				$programme_data['submit_prog_active'] = $STRING['ACTIVE'];
				$programme_data['submit_prog_active_flag'] = 't';	
			}else{
				$programme_data['submit_prog_active'] = $STRING['NOTACTIVE'];
			}
		}
		
		//get series dimensions
		$programme_data['tot_progs'] = $db->getOne("SELECT count(*) FROM programme WHERE series_id = '$programme_data[series_id]'");
		$programme_data['progs_to_run'] = $db->getOne("SELECT count(*) FROM programme WHERE series_id = '$programme_data[series_id]' AND intime > '" . date("Y-m-d H:i:s") . "'");
	
		$smarty->assign($programme_data); 
		
		//prepare GET stuff (if called from series management interface)
		if(isset($_GET['sid'])){
			$my_id = $_GET['id'];						# need to preserve value for smarty's later use
			$_GET['id'] = $_GET['sid'];
			unset($_GET['sid']);
			unset($_GET['action']);
			reset($_GET);
			while(list($key,$val) = each($_GET)){
				$get_stuff[] = $key . "=" . $val;
			}
			$get_stuff = implode("&",$get_stuff);
		}
		
		//assign default data to drop down boxes (if admin)
		$smarty->assign(array(
													"special_needs" 				=> array(""=>$STRING['NONE'],"na"=>$STRING['NA'],"pp"=>$STRING['PP']),
													"series_owner" 					=> $db->getAssoc("SELECT auth_id, name FROM user_map WHERE access_id < 4 ORDER BY name"),
													"series_active" 				=> array('t'=>$STRING['ACTIVE'],'f'=>$STRING['NOTACTIVE']),
													"prog_active" 					=> array('t'=>$STRING['ACTIVE'],'f'=>$STRING['NOTACTIVE']),
													"submit_special_needs" 	=> $prog_special,
													"submit_series_owner"		=> $programme_data['series_owner_id'],
													"series_id" 						=> $programme_data['series_id'],
													"programme_id" 					=> $my_id,
													"get_stuff"							=> $get_stuff
												));
	}//end IF NO Submit
	
	//page output :)	
	pageFinishPopup('showprogrammedetails.htm');							# enter the desired template name as a parameter
?>