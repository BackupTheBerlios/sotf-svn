<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id: login.php,v 1.4 2003/09/25 07:46:12 andras Exp $
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Author: Martin Schmidt, ptmschmidt@fh-stpoelten.ac.at
 */
 

require("init.inc.php");

$username = sotf_Utils::getParameter('userid');
$email = sotf_Utils::getParameter('email');
$okURL = sotf_Utils::getParameter('okURL');

if($username && $email)
{
	  $temp_user=new sotf_User();
	  $storage = $temp_user->getStorageObject();
	  $fields['userid'] = $temp_user->getUserid($username);
	  if($fields['userid']!=NULL)$data=$storage->userDbSelect($fields);
	  
	 if($email==$data['email'] && $username==$data['username']){
	 $new_password=sotf_Utils::randString(6);

	 global $page;
	 $login_href = "http://".$_SERVER['HTTP_HOST'].$config['localPrefix']."/login.php";
	 $subject = $page->getlocalized("pass_mail_subject");
	 $message = $page->getlocalizedWithParams("pass_mail_message", $username, $new_password, $login_href);
	 
	 mail($email, $subject, $message, "From: no-reply@streamonthefly.org\r\nX-Mailer: PHP/" . phpversion() . "\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=utf-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n");
	 $fields['password']=$new_password;
	 $fields['email']=$email;
	 $storage->userDbUpdate($fields);
	 
	 }
	 else{
	 
	 $errorMsg = $page->getlocalized("new_pass_error");
	 
	 }
	 
	  
	if(!$errorMsg)
	{
		if ($okURL)
		{
			$page->redirect($okURL);
		}
		else
		{
			$page->redirect('index.php');
		}
		exit;
	}
}

elseif(!$username && !$email){}
else{
	$errorMsg = $page->getlocalized("missing_parameters");
}

$smarty->assign(
                array('ERRORMSG' => $errorMsg,
                      'USERID'     => $username,
                      'OK_URL' => htmlspecialchars($okURL),
                      'REGISTER_URL' => "register.php?okURL=" . urlencode($okURL)
                      )
                );

excludeRobots();
                      
$page->send();

?>
