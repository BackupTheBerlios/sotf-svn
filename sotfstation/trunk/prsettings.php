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
	$myNav->add("Present Box",'prsettings.php');		# add entry to Navigation Bar Stack
	$pdb=DB::connect("pgsql://" . PDB_USER . ":" . PDB_PASS . "@" . PDB_HOST . "/" . PDB_NAME);
	
	/**
	 * setVar()
	 * 
	 * @param $name
	 * @param $value
	 * @return 
	 */
	function setVar($name, $value) {
	  global $pdb;
	  $name = htmlspecialchars(trim($name));
	  $value = htmlspecialchars(trim($value));
	  $pdb->query("UPDATE goodie_vars SET value='$value' WHERE name='$name'");
	  if($pdb->affectedRows() == 0) {
		 $pdb->query("INSERT INTO goodie_vars (name,value) VALUES('$name', '$value')");
	  }
	}
	
	//process POST
	if($_POST['save_general']) {
		//save data
  	setVar('admin_number', $_POST['admin_number']);
  	setVar('phone_number', $_POST['phone_number']);
  	setVar('email', $_POST['email']);
  	setVar('notification_subject', $_POST['subject']);
		
		//save files
		if($_FILES['intro']['tmp_name']) {
    	$uploadOK = move_uploaded_file($_FILES['intro']['tmp_name'], $config['recorded_dir'] . "/intro.wav");
    }
		if($_FILES['help']['tmp_name']) {
    	$uploadOK = move_uploaded_file($_FILES['help']['tmp_name'], $config['recorded_dir'] . "/help.wav");
    }
		if($_FILES['navigation']['tmp_name']) {
    	$uploadOK = move_uploaded_file($_FILES['navigation']['tmp_name'], $config['recorded_dir'] . "/navigation.wav");
    }
		if($_FILES['applicant_intro']['tmp_name']) {
    	$uploadOK = move_uploaded_file($_FILES['applicant_intro']['tmp_name'], $config['recorded_dir'] . "/applicant_intro.wav");
    }
		if($_FILES['applicant_help']['tmp_name']) {
    	$uploadOK = move_uploaded_file($_FILES['applicant_help']['tmp_name'], $config['recorded_dir'] . "/applicant_help.wav");
    }
		
		//confirm
  	header("Location: confirm.php?action=5&next=prsettings");
		exit;
	}
	
	//get vars
	$vars = $pdb->getAssoc("SELECT name, value FROM goodie_vars");
	$smarty->assign('ADMIN_NUMBER',$vars['admin_number']);
	$smarty->assign('PHONE_NUMBER',$vars['phone_number']);
	$smarty->assign('EMAIL',$vars['email']);
	$smarty->assign('SUBJECT',$vars['notification_subject']);
	$smarty->assign('VARS',$vars);
	
	$intro_file = is_file($config['recorded_dir']."/intro.wav");
	$smarty->assign('intro_file', $intro_file);

	$help_file = is_file($config['recorded_dir']."/help.wav");
	$smarty->assign('help_file', $help_file);

	$navigation_file = is_file($config['recorded_dir']."/navigation.wav");
	$smarty->assign('navigation_file', $navigation_file);

	$applicant_intro_file = is_file($config['recorded_dir']."/applicant_intro.wav");
	$smarty->assign('applicant_intro_file', $applicant_intro_file);

	$applicant_help_file = is_file($config['recorded_dir']."/applicant_help.wav");
	$smarty->assign('applicant_help_file', $applicant_help_file);

	//create help message
	//$myHelp = new helpBox(2,'90%');										# this will fetch a help message from the database and output it
																											# in the template (if allowed to do so)																						
	//page output :)	
	pageFinish('prsettings.htm');												# enter the desired template name as a parameter
?>