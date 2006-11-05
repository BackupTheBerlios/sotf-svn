<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id: admin.php 554 2006-04-12 10:37:20Z buddhafly $
 * Author: András Micsik 
 */

require("init.inc.php");

$smarty->assign('PAGETITLE',$page->getlocalized('AdminPage'));

$page->forceLogin();

//$page->errorURL = "admin.php";

checkPerm('node', 'change');

$userId = sotf_Utils::getParameter('id');
$user = new sotf_User($userId);
$smarty->assign("USER", $user);
$uData = sotf_UserData::getSmartyData($userId);
//unset($uData['id']);
//unset($uData['user_id']);
$smarty->assign("UDATA", $uData);

$page->sendPopup();

?>
