<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/

	/************************
	* Index Page - Login Framework
	*----------------------------------------
	* The purpose of this file is to process user authorization
	* either using the existing connection to SADM oresle make
	* an XMLRCP call.
	************************/
	include("init.inc.php");												# include the global framwork
	$myNav->add($SECTION['LOGIN'],'index.php');			# add entry to Navigation Bar Stack
	
	//is the user already logged in?
	if(is_object($_SESSION['USER'])){
		header("Location: inside.php");								# redirect to the inside of the application
		exit;																					# end script processing.
	}
	
	################################ AUTOLOGIN ########################################################
	//check for autologin 
	if(!empty($_COOKIE['auto_login_id'])){	# there exists a mark
		//lets give it a try then...
		if($db->getOne("SELECT auth_id FROM user_autologin WHERE auth_id = '$_COOKIE[auto_login_id]' AND next_key = '$_COOKIE[auto_login_key]'")){
			//great, valid
			//update keys
			$new_key = md5(uniqid(microtime(),1));
			$db->query("UPDATE user_autologin SET next_key = '$new_key' WHERE auth_id = '" . $_COOKIE[auto_login_id] . "'");
			
			//prepate values
			$res['auth_id'] = $_COOKIE['auto_login_id'];
			$_POST['user'] = $_COOKIE['auto_login_name'];
			
			//set cookies
			setcookie("auto_login_id",$_COOKIE['auto_login_id'],time()+7776000);
			setcookie("auto_login_key",$new_key,time()+7776000);
			setcookie("auto_login_name",$_COOKIE['auto_login_name'],time()+7776000);
			
			//process session init
			include("common/loginmod.inc.php");
		}else{	# the mark is invalid
			//clean the mark
			setcookie("auto_login_id");
			setcookie("auto_login_key");
			setcookie("auto_login_name");
		}
	}
	############################## END AUTOLOGIN ######################################################
	
	############################# PROCESS SUBMIT CALL #################################################
	//process login call
	if($_POST['login']){
		//clean inputs
		$_POST = clean($_POST);
		
		//choose an authentication method
		if(DIRECTSADM_ACCESS){												# SADM is found on local server
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
			
			//send query
			$res = $sdb->getRow("SELECT auth_id FROM authenticate WHERE username = '" . $_POST['user'] . "' AND passwd = '" . $_POST['pass'] . "'",DB_FETCHMODE_ASSOC);
			
			//filter response
			if((!empty($res)) and (!$myError->getLength())){	# user and password match
				include('common/loginmod.inc.php');	
			}else{																						# user and password don't match
				$myError->add($ERR[1]);													# add error to error stack
			}
						
		}else{															# SADM is found on remote server
			//create XMLRPC connection
			include("xmlrpc/xmlrpc.inc");			# include libraries
			
			//create client
			$client = new xmlrpc_client(SADM_SERVER,SADM_HOST,SADM_PORT);
			
			//send query
			$message = new xmlrpcmsg('sadm.authorize',array(new xmlrpcval($_POST['user'],'string'), new xmlrpcval($_POST['pass'],'string')));
			$response = $client->send($message);
			
			//filter response
			if(!$response){										# probably no connection to server
				$myError->add($ERR[2]);					# add an error to the error stack
			}else{														# we have a hit...
				//does the response have a value?														
				if($response->value()){					# user exists and is valid
					$val = xmlrpc_decode($response->value());
					
					//initialize user
					$_SESSION['USER'] = new User($val['id']);
					$_SESSION['USER']->setAll($val);
					
					//redirect
					header("Location: inside.php");
					exit;
				}else{													# user does not exist or other error has appears at server
					$myError->add($ERR[5] . ": " . $response->faultString());
				}	
			}
		}
		
		//assign posts
		$smarty->assign('user',$_POST['user']);
	}
	########################################### END PROCESS SUBMIT #################################
	
	//create help message
	$myHelp = new helpBox(1);
	
	//page output :)
	pageFinish('login.htm');
?>