<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/

	/************************
	* UsersEdit - edit data of existing users
	*----------------------------------------
	* This is a popup from which you will be able to edit existing user's data
	************************/
	include("init.inc.php");	# include the global framwork
	authorize('edit_users');	# check access rights
	
	//process submit
	if($_POST['Submit']){
		$_POST = clean($_POST);
		
		if($db->getOne("SELECT access_id FROM user_map WHERE auth_id = '$_GET[id]'") == 1 AND $_POST['access_level'] != 1){
			if($db->getOne("SELECT count(*) FROM user_map WHERE access_id = 1")<=1){
				$myError->add($ERR[14]);
			}
		}
		
		if($myError->getLength()==0){
			$db->query("UPDATE user_map SET access_id = '$_POST[access_level]', role = '$_POST[role]' WHERE auth_id = '$_GET[id]'");
			$smarty->assign(array("window_destroy"=>true,"destination"=>"users.php"));
		}
	}
	
	//process data
	$myData = $db->getRow("SELECT * FROM user_map WHERE auth_id = '$_GET[id]'",DB_FETCHMODE_ASSOC);
	$smarty->assign(array(
												"user_id"=>$myData['auth_id'],
												"user_mail"=>$myData['mail'],
												"submit_access_level"=>$myData['access_id'],
												"submit_role"=>$myData['role'],
												"user_fname"=>$myData['name']
												));
	
	//process GET array
	//the purpose is to keep the order of things on the parent page after the changes
	//have been made
	$myGet = array();
	reset($_GET);
	while(list($key,$val)=each($_GET)){
		$myGet[] = $key ."=". $val;
	}
	$smarty->assign("get_data",implode("&",$myGet));
	
	//output possible access levels
	$smarty->assign("access_levels",$db->getAssoc("SELECT id, name FROM user_access ORDER BY id"));
	
	//get roles
	include('common/getroles.inc.php');
	$smarty->assign("roles",$myroles);
	
	//get user's series
	$smarty->assign("myseries",$db->getAssoc("SELECT id, title FROM series WHERE owner = '$_GET[id]'"));
											
	//page output :)	
	pageFinishPopup('usersedit.htm');							# enter the desired template name as a parameter
?>