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
	
	function redirect($destination='index.php'){
		global $_GET;
		
		unset($_GET['action']);
		reset($_GET);
		while(list($key,$val)=each($_GET)){
			$OPTIONS[] = $key . "=" . $val;
		}
		$destination = $destination . "?" . implode("&",$OPTIONS);
		header("Location: $destination");
		exit;
	}
	
	################# PROCESS ACTIONS ##########################################################
	if($_GET['action']=='delete'){
		//check if the user owns any series
		if($db->getOne("SELECT count(*) FROM series WHERE owner = '$_GET[id]'") > 0){
			$myError->add($ERR[6]);
		}
		
		if($db->getOne("SELECT access_id FROM user_map WHERE auth_id = '$_GET[id]'") == 1){
			if($db->getOne("SELECT count(*) FROM user_map WHERE access_id = 1")<=1){
				$myError->add($ERR[7]);
			}
		}
		
		if($myError->getLength()==0){
			$db->query("DELETE FROM user_map WHERE auth_id = '$_GET[id]'");
			redirect('users.php');
		}
	}
	
	
	
	################# END PROCESS ACTIONS ######################################################
	
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