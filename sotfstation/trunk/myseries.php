<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/

	/************************
	* myseries - shows the series of some user
	*----------------------------------------
	* This page will display a list of all the possible users registered with
	* this station and their access levels. The Administrator can always
	* add new users to the list or take away privileges or even delete them...
	************************/
	include("init.inc.php");							# include the global framwork
	include("classes/pageSplit.class.php");				# include the page splitting utility
	$myNav->add($SECTION[SERIES],'myseries.php');		# add entry to Navigation Bar Stack
	authorize('edit_series');							# check access rights
	$me = $_SESSION['USER']->get("auth_id");			# global ME :)
	
	//can I edit this?
	if($_GET['id']){
		if($_SESSION['USER']->get("auth_id") == $db->getOne("SELECT series.owner FROM series WHERE series.id = '$_GET[id]'")){
			$mod = true;
		}
	}
	
	if(($_SESSION['USER']->get("edit_station")==2) or ($mod)){
		$mod_flag = TRUE;
	}
	
	################# PROCESS POST ACTION ######################################################
	
	
	
	################# PROCESS ACTIONS ##########################################################
	if($_GET['action']=='delete' and $mod_flag){
		$db->query("DELETE FROM programme WHERE id = '$_GET[pid]'");
	}else if($_GET['action']=='deactivate' and $mod_flag){
		$db->query("UPDATE programme SET active = 'f' WHERE id = '$_GET[pid]'");
	}else if($_GET['action']=='activate' and $mod_flag){
		$db->query("UPDATE programme SET active = 't' WHERE id = '$_GET[pid]'");
	}
	
	
	
	################# END PROCESS ACTIONS ######################################################
	
	//define order by strings
	switch ($_GET['sortby']){
		case 'title':				{$sortstring = 'special'; break;}
		case 'intime':				{$sortstring = 'intime'; break;}
		case 'outtime':				{$sortstring = 'outtime'; break;}
		case 'special':				{$sortstring = 'title'; break;}
		case 'options':				{$sortstring = 'published'; break;}
		default:					{$sortstring = 'intime';}
	}
	
	if(!isset($_GET['orderby']) and isset($_GET['sortby'])){
		$_GET['orderby'] = 'asc';
	}
	
	if(!isset($_GET['sortby'])){
		$_GET['sortby'] = 'intime';
	}
	
	if($_GET['orderby']=='asc'){
		//
	}else{
		$sortstring .= ' DESC';
		$_GET['orderby'] = 'desc';
	}
	
	if(!isset($_GET['id'])){
		$_GET['id'] = $db->getOne("SELECT id FROM series WHERE owner = '$me' LIMIT 1");
	}
	
	if($_GET['id']){
		//get all the possible programme data
		$total = $db->getOne("SELECT count(*) AS tot FROM programme WHERE series_id = '$_GET[id]'");
		$db_result = $db->getAll("SELECT
																	programme.id,
																	programme.title,
																	to_char(programme.intime,'DD-MM-YYYY HH24:MI') AS prog_sd,
																	to_char(programme.outtime,'DD-MM-YYYY HH24:MI') AS prog_ed,
																	programme.special,
																	programme.active,
																	programme.published
																	FROM
																	programme
																	WHERE series_id = '$_GET[id]'
																	ORDER BY $sortstring
																	LIMIT " . $_SESSION['USER']->get("per_page") . " OFFSET $db_block
																");
																
		//get published statuses
		while(list($key,$val) = each($db_result)){
			if(!empty($val[6])){	//programme has been queued for sunc with the node
				if(file_exists(SYNC_DIR . SOTF_STATION_ID . "_" . $val[0] . ".tgz")){
					$db_result[$key]['status'] = 2;	# processing
				}else{
					$db_result[$key]['status'] = 9; # published
				}
			}else{
				//check dir structure
				if(!is_dir(PROG_DIR)){
					mkdir(PROG_DIR);
				}
			
				if(!is_dir(PROG_DIR . $val[0])){
					mkdir(PROG_DIR . $val[0], 0777);
					mkdir(PROG_DIR . $val[0] . "/XBMF", 0777);
					mkdir(PROG_DIR . $val[0] . "/XBMF" . "/audio", 0777);
					mkdir(PROG_DIR . $val[0] . "/XBMF" . "/files", 0777);
				}
			
				//is there audio data?
				$audioflag = false;
				$d = dir(PROG_DIR . $val[0] . "/XBMF" . "/audio");
				while (false !== ($entry = $d->read())) {
    			if(eregi("\.mp3$",$entry)){
						$audioflag = true;
						break;
					}
				}
				$d->close();
			
				if($audioflag){
					$db_result[$key]['status'] = 1;	# not published but ready
				}else{
					$db_result[$key]['status'] = 0;	# no audio
				}
			}
		}
	}
	$smarty->assign("db_result",$db_result);
	
	//make a split
	$smarty->assign(array("block"=>$_GET['block'],"order"=>$_GET['orderby'],"sortby"=>$_GET['sortby']));
	$mySplit = new pageSplit($_GET['block'],$total,"myseries.php",$_SESSION['USER']->get("per_page"));
	$smarty->assign("pageSplit",$mySplit->out());
	
	//get list of series of this person
	$go = $db->getAssoc("SELECT id, title FROM series WHERE owner = '$me'");
	while(list($key,$val)=each($go)){
		$new_go['myseries.php?id='.$key] = $val;
	}
	$smarty->assign(array(
													"series"=>$new_go,
													"series_selected"=>'myseries.php?id='.$_GET['id'],"id"=>$_GET['id'],
													"filter_active"=>array(0=>"Active And Inactive",1=>"Only Active",2=>"Only Inactive"),
													"filter_published"=>array(0=>"Published And Unpublished",1=>"Only Published",2=>"Only Unpublushed")
												));
	//create help message
	//$myHelp = new helpBox(3,'90%');
				
	//page output :)	
	pageFinish('myseries.htm');											# enter the desired template name as a parameter
?>