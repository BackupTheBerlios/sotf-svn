<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/

	/************************
	* Users - the overview page of all active users on the station
	*----------------------------------------
	* This page will display a list of all the possible users registered with
	* this station and their access levels. The Administrator can always
	* add new users to the list or take away privileges or even delete them...
	************************/
	include("init.inc.php");											# include the global framwork
	include("classes/pageSplit.class.php");				# include the page splitting utility
	$myNav->add($SECTION[USERS],'users.php');			# add entry to Navigation Bar Stack
	
	
	//create help message
	//$myHelp = new helpBox(1,'98%');							# this will fetch a help message from the database and output it
																								# in the template (if allowed to do so)
	
	//define order by strings
	switch ($_GET['sortby']){
		case 'id':					{$sortstring = 'user_map.auth_id';break;}
		case 'name':				{$sortstring = 'user_map.name';break;}
		case 'status':			{$sortstring = 'user_access.id';break;}
		case 'involvement':	{$sortstring = 'involvement';break;}
		case 'lastlogin':		{$sortstring = 'intime';break;}
		default:						{$sortstring = 'user_map.auth_id';}
	}
	
	if(!isset($_GET['orderby'])){
		$_GET['orderby'] = 'asc';
	}
	
	if(!isset($_GET['sortby'])){
		$_GET['sortby'] = 'id';
	}
	
	if($_GET['orderby']=='asc'){
		//
	}else{
		$sortstring .= ' DESC';
		$_GET['orderby'] = 'desc';
	}
	
	//get all the possible user data
	$total = $db->getOne("SELECT count(*) AS tot FROM user_map");
	$db_result = $db->getAll("SELECT
																	user_map.auth_id AS id,
																	user_map.name AS user_name,
																	user_access.name AS access_name,
																	to_char(user_log.intime,'DD-MM-YYYY HH24:MI') AS intime,
																	count(series.id) AS involvement 
																	FROM
																	user_map
																	LEFT JOIN user_access ON (user_map.access_id = user_access.id)
																	LEFT JOIN user_log ON (user_map.auth_id = user_log.auth_id)
																	LEFT JOIN series ON (user_map.auth_id = series.owner)
																	GROUP BY user_map.auth_id, user_map.name, user_access.name, user_access.id, user_log.intime 
																	ORDER BY $sortstring
																	LIMIT " . $_SESSION['USER']->get("per_page") . " OFFSET $db_block
																");
	$smarty->assign("db_result",$db_result);
	
	//make a split
	$smarty->assign(array("block"=>$_GET['block'],"order"=>$_GET['orderby'],"sortby"=>$_GET['sortby']));
	$mySplit = new pageSplit($_GET['block'],$total,"users.php",$_SESSION['USER']->get("per_page"));
	$smarty->assign("pageSplit",$mySplit->out());
																				
	//page output :)	
	pageFinish('users.htm');											# enter the desired template name as a parameter
?>