<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id: register.php 545 2006-03-31 13:31:12Z buddhafly $
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 * 
 * MODIFIED by Martin Schmidt ptmschmidt@fh-stpoelten.ac.at
 */

require("init.inc.php");

debug("realname1", $user->realname);

$filled = sotf_Utils::getParameter('filled');
$username = sotf_Utils::getParameter('username');
$password_old = sotf_Utils::getParameter('password_old'); //ADDED BY Martin Schmidt 05-11-21
$password = sotf_Utils::getParameter('password');
$password2 = sotf_Utils::getParameter('password2');
$realname = sotf_Utils::getParameter('realname');
$email = sotf_Utils::getParameter('email');
$language = sotf_Utils::getParameter('language');
$okURL = sotf_Utils::getParameter('okURL');

$change = $page->loggedIn();

debug("realname2", $realname);

if($filled)
{
	// save changes
	// check data
		
	$error = false;

	if(strlen($username) == 0)
	{
		$error = true;
		$smarty->assign('INVALID_USERNAME',true);
		//$errorMsg = appendWith($errorMsg, $page->getlocalized("invalid_username"));
	}
	// check if username acceptible
	
	if(!$error) {
		$name1 = sotf_Utils::makeValidName($username, 32);
		if ($name1 != $username) {
		  $username = $name1;
		  $smarty->assign('ERRORMSG',$page->getlocalized("illegal_name"));
		  $error = true;
		}
		if(!$change && sotf_User::userNameCheck($username))
		{  // check if username is not already in use
			$error = true;
			$smarty->assign('USERNAME_RESERVED',true);
			//$errorMsg = appendWith($errorMsg, userNameCheck($username));
		}
	}
	
	if(!$change && strlen($password) < 2)
	{
		$error = true;
		$smarty->assign('PASSWORD_SHORT',true);
		//$errorMsg = appendWith($errorMsg, $page->getlocalized("password_too_short"));
	}
	if($password != $password2)
	{
		$error = true;
		$smarty->assign('PASSWORD_MISMATCH',true);
		//$errorMsg = appendWith($errorMsg, $page->getlocalized("password_mismatch"));
	}

	// ADDED BY Martin Schmidt 05-11-21
	
	if($email!=""){
		$regex_email='/[a-z0-9_-]+(\.[a-z0-9_-]+)*@([0-9a-z][0-9a-z-]*[0-9a-z]\.)+([a-z]{2,4}|museum)/i';
		if(!preg_match($regex_email, $email)){
			$smarty->assign('BAD_EMAIL',true);
			$error = true;
		}
	}
	else {
		$smarty->assign('EMAIL_MISSING',true);
		$error = true;
	}

	
	if($page->loggedIn()){
	  $storage = $user->getStorageObject();
	  $fields['password'] = $password_old;
	  $fields['username'] = $username;
	  $valid_pwd = $storage->userCheckPwd($fields);
		
		if($password_old==""){
			$error = true;
			$smarty->assign('PASSWORD_EMPTY',true);
		}
		elseif(!$valid_pwd){
			$error = true;
			$smarty->assign('PASSWORD_MISMATCH_OLD',true);
		}
	
	}
	// ------------------

	if(!$error) {
	  $page->setUILanguage($language);
	  if($change) { // existing user
		 $user->realname = $realname;
		 $user->language = $language;
		 $user->email = $email;
		 $user->save($password);
	  } else { 
		 // new user
		 $error = sotf_User::register($password, $username, $realname, $language, $email);
		 if(!$error) {
			$error = sotf_User::login($username, $password);
		 }
		 if($error)
			$smarty->assign('ERRORMSG',$error);
	  }
	  if(!$error) {
		 if ($okURL) {
			$page->redirect($okURL);
		 } else {
			$page->redirect('index.php');
		 }
		 exit;
	  }
	}
} elseif(isset($user)) {
  $username = $user->name;
  $realname = $user->realname;
  $language = $user->language;
  $email = $user->email;
}


$smarty->assign('LANGUAGES',$config['outputLanguages']);

$smarty->assign(array(
					"USERID"     => $username,
					"REALNAME"   => $realname,
					"LANGUAGE"   => $language,
					"EMAIL"      => $email,
					"OK_URL" => htmlspecialchars($okURL),
					"REGISTER_URL" => "register.php?okURL=" . urlencode($okURL)
));

$smarty->assign("if_logged_in", $page->loggedIn());

if($page->loggedIn())
	{
		$smarty->assign("USER_FIELD", "$username<input type=\"hidden\" name=\"username\" value=\"$username\" />
										<input type=\"hidden\" class=\"textfield\" name=\"change\" value=\"1\" />");
		$smarty->assign("SUBMIT_TEXT", $page->getlocalized("Change"));
	}
else
	{
		$smarty->assign("USER_FIELD", "<input type=\"text\" id=\"username\" name=\"username\" class=\"textfield\" value=\"$username\" /> *");
		$smarty->assign("SUBMIT_TEXT", $page->getlocalized("Register"));
	}

excludeRobots();

$page->send();

?>
