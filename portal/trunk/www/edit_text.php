<?php

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: Máté Pataki, András Micsik
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 * 
 */

//$anhour = gmdate('D, d M Y H:i:s T', gmmktime(gmdate("H"),gmdate("i")+60,0,gmdate("m"),(gmdate("d")),gmdate("Y")));
//header("Expires: $anhour");

require("portal_login.php");

if ($portal->isAdmin($user->getId()))
{
	$id = sotf_Utils::getParameter('id');			//programme id
	$text = sotf_Utils::getParameter('text');		//is text
	$teaser = sotf_Utils::getParameter('teaser');		//is teaser
	$save = sotf_Utils::getParameter('save');		//save button pressed
	$value = sotf_Utils::getParameter('value');		//save button pressed
	
	$prgprop = $portal->getPrgProperties($id);
	
	if ($save)		//if save button pressed
	{
		if ($text) $prgprop['text'] = $value;
		elseif ($teaser) $prgprop['teaser'] = $value;
		$portal->setPrgProperties($id, $prgprop['text'], $prgprop['teaser']);
		$page->redirect($rootdir."/closeAndRefresh.php");		//close window and go back to edit mode
	}
	else
	{
		if ($text) $value = $prgprop['text'];
		elseif ($teaser) $value = $prgprop['teaser'];
	}
	
	$settings = $portal->loadSettings();
	
	////Smarty
	$smarty->assign("portal", $settings["portal"]);
	$smarty->assign("rootdir", $rootdir);				//root directory (portal/www)
	$smarty->assign("php_self", $_SERVER['PHP_SELF']);		//php self for the form submit and hrefs
	$smarty->assign("portal_name", $portal_name);			//name of the portal
	
	$smarty->assign("id", $id);
	$smarty->assign("value", $value);
	$smarty->assign("text", $text);
	$smarty->assign("teaser", $teaser);
	$smarty->assign("title", sotf_Utils::getParameter('title'));
	
	$page->send("edit_text.htm");
}
?>