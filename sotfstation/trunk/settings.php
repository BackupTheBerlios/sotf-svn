<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/

	/************************
	* Settings - lets the user manage his/her client appearance settings
	************************/
	include("init.inc.php");													# include the global framwork
	$myNav->add($SECTION[SETTINGS],'settings.php');		# add entry to Navigation Bar Stack
	
	//process submit
	if($_POST['Submit']){
		//clean POST
		$_POST = clean($_POST);
		
		//check for errors
		#check inputs
		if(!$myError->checkMail($_POST['mail'])){
			$myError->add($ERR[9]);
		}
		
		if(!$myError->checkLength($_POST['name'])){
			$myError->add($ERR[11]);
		}
		
		//errorz?
		if($myError->getLength()==0){	# no errorz :)
			//fix values
			if($_POST['autologin']!='t'){
				$_POST['autologin'] = 'f';
			}
			
			//update database
			$db->query("UPDATE user_map SET name = '$_POST[name]', mail = '$_POST[mail]', autologin = '$_POST[autologin]', per_page = '$_POST[per_page]' WHERE auth_id = '" . $_SESSION['USER']->get("auth_id") . "'");
			
			//redirect to confirm
			header("Location: confirm.php?action=4&next=settings");
		}else{ # errorz :(
			$smarty->assign(array(
															"submit_name" 			=> $_POST['name'],
															"submit_mail"				=> $_POST['mail'],
															"submit_per_page"		=> $_POST['per_page'],
															"submit_autologin"	=> $_POST['autologin'],
															"access_level"			=> $db->getOne("SELECT user_access.name FROM user_map LEFT JOIN user_access ON (user_map.access_id = user_access.id) WHERE auth_id = '" . $_SESSION['USER']->get("auth_id") . "'")
														));
		}
	}else{
		//get data
		$smarty->assign($db->getRow("SELECT 
																	user_map.name AS submit_name, 
																	user_map.mail AS submit_mail, 
																	user_map.per_page AS submit_per_page,
																	user_map.autologin AS submit_autologin,
																	user_access.name AS access_level
															 	FROM
															 		user_map
															 	LEFT JOIN user_access ON (user_map.access_id = user_access.id)
															 	WHERE auth_id = '" . $_SESSION['USER']->get("auth_id") . "'",DB_FETCHMODE_ASSOC));
	}
	
	//drop down fill											 
	$smarty->assign("per_page",array(5=>5,10=>10,15=>15,20=>20,25=>25,30=>30,35=>35,40=>40,45=>45,50=>50));
	
	//create help message
	$myHelp = new helpBox(2,'98%');										# this will fetch a help message from the database and output it
																										# in the template (if allowed to do so)																						
	//page output :)	
	pageFinish('settings.htm');												# enter the desired template name as a parameter
?>