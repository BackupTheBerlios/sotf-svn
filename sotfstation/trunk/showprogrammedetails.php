<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/

	/************************
	* Show Programme Details
	*----------------------------------------
	* This is a popup that will show all the details of the desired programme
	************************/
	include("init.inc.php");	# include the global framework
	
	//get all the default data
	$programme_data = $db->getRow("SELECT 
									to_char(programme.intime,'DD-MM-YYYY') AS prog_date,
									to_char(programme.intime,'HH24:MI') AS prog_intime,
									to_char(programme.outtime,'HH24:MI') AS prog_outtime,
									programme.title AS prog_title,
									programme.special AS prog_special,
									programme.series_id AS series_id,
									series.title AS series_title,
									series.description AS series_desc,
									user_map.name AS series_owner,
									user_map.auth_id AS series_owner_id
							 FROM programme 
							 LEFT JOIN series ON (programme.series_id = series.id)
							 LEFT JOIN user_map ON (series.owner = user_map.auth_id)
							 WHERE programme.id = '$_GET[id]'",DB_FETCHMODE_ASSOC);
	
	switch($programme_data['prog_special']){
		case 'na'	:{$programme_data['prog_special'] = $STRING['NA']; break;}
		case 'pp'	:{$programme_data['prog_special'] = $STRING['PP']; break;}
		default		:{$programme_data['prog_special'] = $STRING['NONE'];}
	}
	
	if(empty($programme_data['prog_title'])){
		$programme_data['prog_title'] = $STRING['NONE'];
	}
	
	$programme_data['tot_progs'] = $db->getOne("SELECT count(*) FROM programme WHERE series_id = '$programme_data[series_id]'");
	$programme_data['progs_to_run'] = $db->getOne("SELECT count(*) FROM programme WHERE series_id = '$programme_data[series_id]' AND intime > '" . date("Y-m-d H:i:s") . "'");
	
	$smarty->assign($programme_data);
							 
	
											
	//page output :)	
	pageFinishPopup('showprogrammedetails.htm');							# enter the desired template name as a parameter
?>