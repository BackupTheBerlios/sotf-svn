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
		exit;																					# end script processing
	}
	
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
			$res = $sdb->getRow("SELECT auth_id, primary_account FROM authenticate WHERE username = '" . $_POST['user'] . "' AND passwd = '" . $_POST['pass'] . "'",DB_FETCHMODE_ASSOC);
			
			//filter response
			if((!empty($res)) and (!$myError->getLength())){	# user and password match
				//process login
				$_SESSION['USER'] = new User($res['auth_id']);
				$_SESSION['USER']->set("name",$_POST['user']);
				$_SESSION['USER']->set("auth_id",$res['auth_id']);
			
				//is this user a SADM primary user?	mark only if true
				if($res['primary_account']=='t'){
					$_SESSION['USER']->set("primary_account",$res['primary_account']);
				}
				
				//get group data
				$membername = $sdb->getRow("select base_id, ent_name from authenticate, base_entities where username ='" . $_POST['user'] . "' and base_id=general_id");
				$_SESSION['USER']->set("group_name",$membername[1]);
				$_SESSION['USER']->set("group_id",$membername[0]);
			
				//get additional local user related acces level data
				$_SESSION['USER']->set("per_page",20);
				
				//get access permissions
				$_SESSION['USER']->setAll($db->getRow("SELECT edit_series, edit_station, edit_users FROM user_map LEFT JOIN user_access ON (user_map.access_id = user_access.id) WHERE user_map.auth_id = '$res[auth_id]'",DB_FETCHMODE_ASSOC));
			
				//log info (mark the user that he logged in)
				$myLog->add($res['auth_id'],0);
				
				//redirect
				header("Location: inside.php");	# to the inside of the application
				exit;														# exit the processing of the code
			}else{														# user and password don't match
				$myError->add($ERR[1]);					# add error to error stack
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
	
	//create help message
	$myHelp = new helpBox(1);
	
	//page output :)
	pageFinish('login.htm');
?>