<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id: login.php 308 2003-09-25 07:46:12Z andras $
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: Andr�s Micsik, M�t� Pataki, Tam�s D�ri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

$username = sotf_Utils::getParameter('userid');
$password = sotf_Utils::getParameter('password');
$okURL = sotf_Utils::getParameter('okURL');

if($username && $password)
{
	$errorMsg = sotf_User::login($username, $password);
	if(!$errorMsg)
	{
		if ($okURL)
		{
			$page->redirect($okURL);
		}
		else
		{
			//$page->redirect('index.php');
			if(isset($_SERVER['HTTP_REFERER']) && basename($_SERVER['HTTP_REFERER']) != "login.php") $page->redirect($_SERVER['HTTP_REFERER']);   //added by Klaus Temper
			else $page->redirect('index.php');  
		}
		exit;
	}
}
else
{
	if($page->loggedIn())
	{
		$errorMsg = $page->getlocalized("relogin");
		$username = $userid;
	}
	elseif($username || $password)
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
