<?
	//process login
	$_SESSION['USER'] = new User($res['auth_id']);
	$_SESSION['USER']->set("name",$_POST['user']);
	$_SESSION['USER']->set("auth_id",$res['auth_id']);
			
	//get additional local user related acces level data
	$data = $db->getRow("SELECT * FROM user_map WHERE auth_id = $res[auth_id]",DB_FETCHMODE_ASSOC);
	$_SESSION['USER']->set("per_page",$data['per_page']);
	$_SESSION['USER']->set("mail",$data['mail']);
	$_SESSION['USER']->set("real_name",$data['name']);
	$_SESSION['USER']->set("role",$data['role']);
				
	//get access permissions
	$_SESSION['USER']->setAll($db->getRow("SELECT edit_series, edit_station, edit_users FROM user_map LEFT JOIN user_access ON (user_map.access_id = user_access.id) WHERE user_map.auth_id = '$res[auth_id]'",DB_FETCHMODE_ASSOC));
			
	//log info (mark the user that he logged in)
	$myLog->add($res['auth_id'],0);
				
	//redirect
	header("Location: inside.php");	# to the inside of the application
	exit;														# exit the processing of the code
?>