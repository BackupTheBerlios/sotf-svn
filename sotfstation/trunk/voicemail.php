<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/

	/************************
	* Template Page Using the pre-build page generation framework
	*----------------------------------------
	* Purpose of page goes here
	************************/
	include("init.inc.php");										# include the global framwork
	$myNav->add("Voice Box",'index.php');				# add entry to Navigation Bar Stack
	//authorize('edit_series');									# check access rights
	
	//connect to voice box db
	$vdb=DB::connect("pgsql://" . VDB_USER . ":" . VDB_PASS . "@" . VDB_HOST . "/" . VDB_NAME);
	
	//check if current user has a voice box
	$vbid = $vdb->getOne("SELECT vb_id FROM vb_data WHERE owner_id = '" . $_SESSION['USER']->get("auth_id") . "'");
	
	//is this a serve request?
	if($_GET['action'] == 'servefile'){
		//check if user owns this file
		$temp = $vdb->getOne("SELECT vb_id FROM vb_messages WHERE msg_id = '$_GET[id]'");
		
		if($vbid == $temp){
		
			if($_GET['action'] == 'servefile'){
				$fileName = $vdb->getOne("SELECT msg_file_name FROM vb_messages WHERE msg_id = '$_GET[id]'");
				header ("Location: " . VBBASEURL . "/message_dir/" . $vbid . "/" . $fileName);
			}else if($_GET['action'] == 'deletefile'){
				$vdb->query("DELETE FROM vb_messages WHERE msg_id = '$_GET[id]'");
				header ("Location: voicemail.php");
			}
			
		}else{
			header("Location: noaccess.php");
		}
		exit;
	}elseif($_GET['action'] == 'delete'){
		$vdb->query("DELETE FROM vb_messages WHERE msg_id = '$_GET[id]'");
	}
	
	//sort strings
	//define order by strings
	switch ($_GET['sortby']){
		case 'msg_caller_number':	{$sortstring = 'msg_caller_number';break;}
		case 'msg_ts':						{$sortstring = 'msg_ts';break;}
		default:									{$sortstring = 'msg_caller_number';}
	}
	
	if(!isset($_GET['orderby'])){
		$_GET['orderby'] = 'asc';
	}
	
	if(!isset($_GET['sortby'])){
		$_GET['sortby'] = 'msg_caller_number';
	}
	
	if($_GET['orderby']=='asc'){
		//
	}else{
		$sortstring .= ' DESC';
		$_GET['orderby'] = 'desc';
	}
	
	//if user has לטרג hurray!
	if($vbid > 0){
		$smarty->assign("db_result",$vdb->getAll("SELECT msg_caller_number, msg_ts, msg_id FROM vb_messages WHERE vb_id = '$vbid' ORDER BY $sortstring"));
		$smarty->assign("sortby",$_GET['sortby']);
		$smarty->assign("order",$_GET['orderby']);
	}else{
		$smarty->assign("failed",true);
	}
	
	//create help message
	//$myHelp = new helpBox(1);									# this will fetch a help message from the database and output it
																							# in the template (if allowed to do so)
														
	//page output :)
	pageFinish('voice.htm');								# enter the desired template name as a parameter
	//pageFinishPopup('noaccess.htm');					# same as above but in a popop
?>
