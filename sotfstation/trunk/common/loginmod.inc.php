<?
	//process login
	$_SESSION['USER'] = new User($res['auth_id']);
	$_SESSION['USER']->set("name",$_POST['user']);
	$_SESSION['USER']->set("auth_id",$res['auth_id']);
	$_SESSION['USER']->set("vbnum",$vbnum);
			
	//get additional local user related acces level data
	$data = $db->getRow("SELECT * FROM user_map WHERE auth_id = $res[auth_id]",DB_FETCHMODE_ASSOC);
	$_SESSION['USER']->set("per_page",$data['per_page']);
	$_SESSION['USER']->set("mail",$data['mail']);
	$_SESSION['USER']->set("real_name",$data['name']);
	$_SESSION['USER']->set("role",$data['role']);
				
	//get access permissions
	$_SESSION['USER']->setAll($db->getRow("SELECT user_access.edit_series, user_access.edit_station, user_access.edit_users, user_access.edit_presentbox 
																					FROM user_map LEFT JOIN user_access ON (user_map.access_id = user_access.id) 
																					WHERE user_map.auth_id = '$res[auth_id]'",DB_FETCHMODE_ASSOC));
	
	//setcookie("user",$_POST['user'],time()+60*15,"/");

	//log info (mark the user that he logged in)
	$myLog->add($res['auth_id'],0);
				
	//redirect
	header("Location: inside.php");	# to the inside of the application
	exit;														# exit the processing of the code
?>