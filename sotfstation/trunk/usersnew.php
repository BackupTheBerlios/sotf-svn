<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/

	/************************
	* Usersnew - add new users to the station admin panel
	*----------------------------------------
	* This page will allow the registration of new users to the station. This simply
	* inludes the creation of a new account.
	* 
	* 2DO - Tie to XMLRPC Interface
	************************/
	include("init.inc.php");												# include the global framwork
	include("classes/sendMail.class.php");					# include the mail sender
	$myNav->add($SECTION[USERS],'users.php');				# add entry to Navigation Bar Stack
	$myNav->add($SECTION[USERSNEW],'usersnew.php');	# add entry to Navigation Bar Stack
	authorize('edit_users');												# check access rights
	
	/**
	 * random_char() - will return a random charachter
	 * 
	 * @param $string -- string to pick a random charachter from
	 * @return char
	 * 
	 * Alexey Koulikov - 12.01.2001
	 */
	function random_char($string){
	  $length = strlen($string);
	  $position = mt_rand(0, $length - 1);
	  return($string[$position]);
  }

 
  /**
   * random_string()
   * 
   * @param $charset_string - the charset from which to construct a random string
   * @param $length - the length of the random string to be constructed
   * @return string
	 * 
	 * Alexey Koulikov - 12.01.2001
   */
  function random_string($charset_string, $length){
	  $return_string = random_char($charset_string);
	  for($x = 1; $x < $length; $x++){
		  $return_string .= random_char($charset_string);
	  }
	  return($return_string);
  }
	
	
	//process request
	if($_POST['Submit']){
		//clean POST
		$_POST = clean($_POST);
		
		//check for errors
		#check inputs
		if(!$myError->checkUser($_POST['login'])){
			$myError->add($ERR[8]);
		}
		
		if(!$myError->checkMail($_POST['mail'])){
			$myError->add($ERR[9]);
		}
		
		if(!empty($_POST['pass'])){
			if(!$myError->checkUser($_POST['pass'])){
				$myError->add($ERR[10]);
			}
		}
		
		if(!$myError->checkLength($_POST['name'])){
			$myError->add($ERR[11]);
		}
		
		#check DB relations
		if(DIRECTSADM_ACCESS){	//using local database
			//create second database connection
			$sdb = DB::connect(array(										# Start a connection to the database
  			'phptype'  => SDB_TYPE,
   			'dbsyntax' => false,
  			'protocol' => false,
 		  	'hostspec' => SDB_HOST,
 		  	'database' => SDB_NAME,
 		  	'username' => SDB_USER,
 		  	'password' => SDB_PASS
			));
			
			//did the connection to SADM database fail?
			if(DB::isError($sdb)){
				$myError->add($ERR[4]);
			}
			
			//check if the user name has been taken or not
			if($sdb->getOne("SELECT username FROM authenticate WHERE username = lower('$_POST[login]')")){
				$myError->add($ERR[12]);
			}
			
			//check if the group exists
			if(!$sdb->getOne("SELECT base_id FROM base_entities WHERE base_id = '" . SADM_GROUP . "'")){
				$myError->add($ERR[13]);
			}
		
			//create new user
			if($myError->getLength()==0){
			
				//what to do wtih passwords?
				if(empty($_POST['pass'])){		#autogenerate a password!
					$_POST['pass'] = random_string('abcdefgxyz369',6);														# generate the pass
					
					//read the file with the e-mail template
					$message_file = fopen("configs/newuser.txt","r");															
					$message = fread($message_file, filesize("configs/newuser.txt"));
					fclose($message_file);
					
					//replace all the needed string with predefined data
					$message = str_replace('{user_name}',$_POST['login'],$message);
					$message = str_replace('{access_pass}',$_POST['pass'],$message);
					$message = str_replace('{station_address}',SRC_ROOT,$message);
					
					//create mail
					$myMailer = new sendMail($_POST['mail'],MAILBOT,$STRING['NEWUSER'],$message);
					
					//send mail
					$myMailer->send();
				}
				
				//create SADM USER
				$sdb->query("INSERT INTO authenticate(username,passwd,user_type,general_id,primary_account) VALUES('" . $_POST['login'] . "','" . $_POST['pass'] . "','member','" . SADM_GROUP . "','false' )");
				$my_new_id = $sdb->getOne("select auth_id from authenticate where username = '" . $_POST['login'] . "'");
				$sdb->query("INSERT INTO user_preferences(auth_id) values('$my_new_id')");
			
				//create STATION USER
				$db->query("INSERT INTO user_map(auth_id,access_id,name,mail) VALUES('$my_new_id','$_POST[access_level]','$_POST[name]','$_POST[mail]')");
				
				//create maildirs
				$fp = @fopen("/var/squirrel/maildir-creation/new-maildirs","a+");
  			@fputs($fp,"$_POST[login]\n");
  			@fclose($fp);
			
				system("/usr/local/bin/maildirmake /home/imap/$_POST[login]");
				
				//redirect
				header("Location: confirm.php?action=1&next=users");
			}else{	# fuck, errorz :)
				$smarty->assign(array("submit_login"=>$_POST['login'],"submit_name"=>$_POST['name'],"submit_mail"=>$_POST['mail'],"submit_pass"=>$_POST['pass'],"submit_access_level"=>$_POST['access_level']));
			}
		}else{
			// NO XMLRPC INTERFACE JUST YET
		}
	}
	
	//output possible access levels
	$smarty->assign("access_levels",$db->getAssoc("SELECT id, name FROM user_access ORDER BY id"));
																	
	//page output :)	
	pageFinish('usersnew.htm');											# enter the desired template name as a parameter
?>