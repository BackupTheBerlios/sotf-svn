<?php

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

debug("realname1", $user->realname);

$filled = sotf_Utils::getParameter('filled');
$username = sotf_Utils::getParameter('username');
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
	if(!$change && sotf_User::userNameCheck($username))
	{  // check if username is not already in use
		$error = true;
		$smarty->assign('USERNAME_RESERVED',true);
		//$errorMsg = appendWith($errorMsg, userNameCheck($username));
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
	// TODO: check email?
	if(!$error)
	{
		if($change) { // existing user
      $user->realname = $realname;
      $user->language = $language;
      $user->email = $email;
      $user->save($password);
		}
		else // new user
		{
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
}
elseif(isset($user))
{
		$username = $user->name;
		$realname = $user->realname;
		$language = $user->language;
		$email = $user->email;
}


$smarty->assign('LANGUAGES',$outputLanguages);

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
	$smarty->assign("USER_FIELD", "$username<INPUT type=\"hidden\" name=\"username\" value=\"$username\"><INPUT type=\"hidden\" name=\"change\" value=\"1\">");
	$smarty->assign("SUBMIT_TEXT", $page->getlocalized("Change"));
}
else
{
	$smarty->assign("USER_FIELD", "<INPUT type=\"text\" name=\"username\" value=\"$username\">");
	$smarty->assign("SUBMIT_TEXT", $page->getlocalized("Register"));
}

$page->send();

?>
