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
	
	//$vdb = DB::connect("pgsql://" . VDB_USER . ":" . VDB_PASS . "@" . VDB_HOST . "/" . VDB_NAME,false);
	
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
				
				//clean cookies
				setcookie("auto_login_id");
				setcookie("auto_login_key");
				setcookie("auto_login_name");
			}else{
				//create autologin keys
				$new_key = md5(uniqid(microtime(),1));
				
				//check if an entry exists
				if($db->getOne("SELECT auth_id FROM user_autologin WHERE auth_id = '" . $_SESSION['USER']->get("auth_id") . "'")){	#yes
					$db->query("UPDATE user_autologin SET next_key = '$new_key' WHERE auth_id = '" . $_SESSION['USER']->get("auth_id") . "'");
				}else{	#no, create one
					$db->query("INSERT INTO user_autologin VALUES('" . $_SESSION['USER']->get("auth_id") . "','$new_key')");
				}
				
				//set cookies
				setcookie("auto_login_id",$_SESSION['USER']->get("auth_id"),time()+7776000);
				setcookie("auto_login_key",$new_key,time()+7776000);
				setcookie("auto_login_name",$_SESSION['USER']->get("name"),time()+7776000);
			}
			
			//update database
			$db->query("UPDATE user_map SET name = '$_POST[name]', mail = '$_POST[mail]', per_page = '$_POST[per_page]' WHERE auth_id = '" . $_SESSION['USER']->get("auth_id") . "'");
			
			//check if an entry exists
			/*
			if($vdb->getOne("SELECT owner_id FROM vb_data WHERE owner_id = '" . $_SESSION['USER']->get("auth_id") . "'")){	#yes
				$vdb->query("UPDATE vb_data SET 
																			owner_telnr = '$_POST[tel]',
																			welcome_msg = '$_POST[mes]',
																			owner_pin = '$_POST[pin]'
											WHERE owner_id = '" . $_SESSION['USER']->get("auth_id") . "'");
											
			}else{	#no, create one
				$vdb->query("INSERT INTO vb_data(owner_id,owner_telnr,owner_pin,welcome_msg) VALUES('" . $_SESSION['USER']->get("auth_id") . "','$_POST[tel]','$_POST[pin]','$_POST[mes]')");
				//also make directory
				mkdir("../pvxmailbox/message_dir/" . $_SESSION['USER']->get("auth_id"));
				//echo $appRecDir . "/" . $_SESSION['USER']->get("auth_id");
			}
			* */
				
			//update session
			$_SESSION['USER']->set("per_page",$_POST[per_page]);
			
			//redirect to confirm
			header("Location: confirm.php?action=4&next=settings");
		}else{ # errorz :(
			$smarty->assign(array(
															"submit_name" 			=> $_POST['name'],
															"submit_mail"				=> $_POST['mail'],
															"submit_per_page"		=> $_POST['per_page'],
															"submit_autologin"	=> $_POST['autologin'],
															"access_level"			=> $db->getOne("SELECT user_access.name FROM user_map LEFT JOIN user_access ON (user_map.access_id = user_access.id) WHERE auth_id = '" . $_SESSION['USER']->get("auth_id") . "'"),
															"submit_tel" 				=> $_POST['tel'],
															"submit_pin" 				=> $_POST['pin'],
															"submit_mes" 				=> $_POST['mes']
														));
		}
	}else{
		//get data
		$smarty->assign($db->getRow("SELECT 
																	user_map.name AS submit_name, 
																	user_map.mail AS submit_mail, 
																	user_map.per_page AS submit_per_page,
																	user_access.name AS access_level
															 	FROM
															 		user_map
															 	LEFT JOIN user_access ON (user_map.access_id = user_access.id)
															 	WHERE auth_id = '" . $_SESSION['USER']->get("auth_id") . "'",DB_FETCHMODE_ASSOC));
		/*
		$smarty->assign($vdb->getRow("SELECT 
																	owner_telnr AS submit_tel, 
																	owner_pin AS submit_pin, 
																	welcome_msg AS submit_mes
															 	FROM
															 		vb_data
															 	WHERE owner_id = '" . $_SESSION['USER']->get("auth_id") . "'",DB_FETCHMODE_ASSOC));
		*/
		//check if autologin is valid
		if($db->getOne("SELECT auth_id FROM user_autologin WHERE auth_id = '$_COOKIE[auto_login_id]' AND next_key = '$_COOKIE[auto_login_key]'")){
			$smarty->assign("submit_autologin","t");
		}
	}
	
	//drop down fill											 
	$smarty->assign("per_page",array(5=>5,10=>10,15=>15,20=>20,25=>25,30=>30,35=>35,40=>40,45=>45,50=>50));
	//$smarty->assign("vbnum",$_SESSION['USER']->get("vbnum"));
	
	//create help message
	$myHelp = new helpBox(2,'90%');										# this will fetch a help message from the database and output it
																										# in the template (if allowed to do so)																						
	//page output :)	
	pageFinish('settings.htm');												# enter the desired template name as a parameter
?>