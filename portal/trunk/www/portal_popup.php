<?php

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: Máté Pataki, András Micsik
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 * 
 */

require("portal_login.php");

$row = sotf_Utils::getParameter('row');
$col = sotf_Utils::getParameter('col');

//$portal = new sotf_Portal("1");	//TODO:xxxxxx
if (!isset($_SESSION["settings"])) $_SESSION["settings"] = $_SESSION["old_settings"];
$portal->setSettings($_SESSION["settings"]);

$cell["resource"] = sotf_Utils::getParameter('resource');

if ($cell["resource"] == sotf_Utils::getParameter('oldresource'))		//not first time here
{
	$cell["value"] = stripslashes(sotf_Utils::getParameter('value'));
//	$cell["value"] = htmlspecialchars(stripslashes(sotf_Utils::getParameter('value')), ENT_QUOTES);
	$cell["link"] = sotf_Utils::getParameter('link');
	$cell["style"] = sotf_Utils::getParameter('style');
	$cell["class"] = sotf_Utils::getParameter('class');
	$cell["align"] = sotf_Utils::getParameter('align');
	$cell["valign"] = sotf_Utils::getParameter('valign');
	$cell["width"] = sotf_Utils::getParameter('width');
	$cell["color"] = sotf_Utils::getParameter('color');
}
else
{
	$cell = $portal->getCell($row, $col);
	$cell["resource"] = sotf_Utils::getParameter('resource');
}


if ($cell["resource"] == 'text')			//if text analyze html code
{
	$html = new html();
	$cell["value"] = $html->analyze_text($cell["value"]);
}



if (sotf_Utils::getParameter('insert_after'))		//insert after button pressed
{
	$portal->setCell($row, $col, $cell);		//save current values
	$portal->insertCell($row, $col, "after");	//insert cell after current
	$_SESSION["settings"]["table"] = $portal->getTable();	//save result
	$col++;						//set current cell tu the new one
}
elseif (sotf_Utils::getParameter('insert_before'))	//insert before button pressed
{
	$portal->setCell($row, $col, $cell);		//save current values
	$portal->insertCell($row, $col, "before");	//insert cell before current
	$_SESSION["settings"]["table"] = $portal->getTable();	//save result
}
elseif (sotf_Utils::getParameter('delete'))		//delete cell button pressed
{
	$portal->deleteCell($row, $col);		//delete current cell
	$_SESSION["settings"]["table"] = $portal->getTable();	//save result
	$page->redirect($rootdir."/closeAndRefresh.php");		//close window and go back to edit mode
}
elseif ($cell["resource"] != NULL)
{
	$portal->setCell($row, $col, $cell);
	////save cuttent portal table to the session
	$_SESSION["settings"]["table"] = $portal->getTable();
	if ($cell["resource"] == sotf_Utils::getParameter('oldresource')) $page->redirect($rootdir."/closeAndRefresh.php");	//if resource type not chnged
}

////SMARTY
$settings = $_SESSION["settings"];
$smarty->assign("portal", $settings["portal"]);
$smarty->assign("css", $settings["css"]);

$smarty->assign("table", $portal->getTable());

$smarty->assign("resources", $portal->getResources());
$smarty->assign("files", $portal->getUploadedFiles());
$smarty->assign("queries", $portal->getQueries());
$smarty->assign("playlists", $portal->getPlaylists());
$smarty->assign("styles", $portal->getStyles());
$smarty->assign("aligns", $portal->getAligns());
$smarty->assign("valigns", $portal->getValigns());
$smarty->assign("colors", $portal->getColors());

$cell = $portal->getCell($row, $col);
$smarty->assign("resource", $cell["resource"]);
$smarty->assign("value", $cell["value"]);
$smarty->assign("link", $cell["link"]);
$smarty->assign("style", $cell["style"]);
$smarty->assign("class", $cell["class"]);
$smarty->assign("align", $cell["align"]);
$smarty->assign("valign", $cell["valign"]);
$smarty->assign("width", $cell["width"]);
$smarty->assign("color", $cell["color"]);

$smarty->assign("row", $row);
$smarty->assign("col", $col);

//directories and names
$smarty->assign("rootdir", $rootdir);				//root directory (portal/www)
$smarty->assign("php_self", $_SERVER['PHP_SELF']);		//php self for the form submit and hrefs
$smarty->assign("portal_name", $portal_name);			//name of the portal


//$smarty->assign("numbers", $portal->getNumbers());
//$smarty->assign("rowlength", $portal->getRowLength());

$page->send("portal_popup.htm");

?>
