<?php
require("init.inc.php");

// todo id must be  sql safe
$id = sotf_Utils::getParameter('id');
$title = sotf_Utils::getParameter('title');
$author = sotf_Utils::getParameter('author');
$ProductionYear = sotf_Utils::getParameter('ProductionYear');
$ProductionMonth = sotf_Utils::getParameter('ProductionMonth');
$ProductionDay = sotf_Utils::getParameter('ProductionDay');
$ExpiryYear = sotf_Utils::getParameter('ExpiryYear');
$ExpiryMonth = sotf_Utils::getParameter('ExpiryMonth');
$ExpiryDay = sotf_Utils::getParameter('ExpiryDay');
$keywords = sotf_Utils::getParameter('keywords');
$contact_email = sotf_Utils::getParameter('contact_email');
$contact_phone = sotf_Utils::getParameter('contact_phone');
$abstract = sotf_Utils::getParameter('abstract');
		
$production_date = sprintf("%04d-%02d-%02d", $ProductionYear, $ProductionMonth, $ProductionDay);
$expiry_date = sprintf("%04d-%02d-%02d", $ExpiryYear, $ExpiryMonth ,$ExpiryDay);

$finishpublish = sotf_Utils::getParameter('finishpublish');
$finishnotpublish = sotf_Utils::getParameter('finishnotpublish');

$okURL = sotf_Utils::getParameter('okURL');

$prg = & new sotf_Programme($id);

// admins or owners can change files
if(!$prg->isEditable()) {
  raiseError("no permission to change files in this programme");
  exit;
}

if ($title)
	$prg->set('title',$title);
if ($author)
	$prg->set('author',$author);
if ($keywords)
	$prg->set('keywords',$keywords);
if ($contact_email)
	$prg->set('contact_email',$contact_email);
if ($contact_phone)
	$prg->set('contact_phone',$contact_phone);
if ($abstract)
	$prg->set('abstract',$abstract);
if ($production_date)
	$prg->set('production_date',$production_date);
if ($expiry_date)
	$prg->set('expiry_date',$expiry_date);

$smarty->assign("PAGETITLE", $page->getlocalized("editmeta"));
$smarty->assign("OKURL", $_SERVER['PHP_SELF'] . "?id=" . rawurlencode($id));

if($id) {
  if ($finishpublish) {
	$prg->publish();
	if ($okURL)
	  $page->redirect($okURL);
	else
	  $page->redirect("editor.php");
  }
  if ($finishnotpublish) {
	$prg->withDraw();
	if ($okURL)
	  $page->redirect($okURL);
	else
	  $page->redirect("editor.php");
  }
  $smarty->assign('ID', $id);

  $prg = & new sotf_Programme($id);
  $smarty->assign_by_ref('PRG', $prg);
  
  if($prg->isEditable()) {
    $smarty->assign('EDIT_PERMISSION', true);
  }

  /* rights have to be get from sotf_files table
  $smarty->assign('RIGHT_DOWNLOAD',$right_download);
  $smarty->assign('RIGHT_DOWNLOAD24',$right_download24);
  $smarty->assign('RIGHT_LISTEN',$right_listen);
  $smarty->assign('RIGHT_LISTEN24',$right_listen24);
  */
  // ??? $smarty->assign('UPLOAD_PERMISSION',$page->getPermission('upload',$idObj->stationId));

  $smarty->assign('OTHERFILES', $prg->listOtherFiles());
  $smarty->assign('AUDIOFILES', $prg->listAudioFiles());

  $smarty->assign('PAGETITLE', htmlspecialchars($prg->get('title')));

  $expiry_date = $prg->get['expiry_date'];
  $production_date = $prg->get['production_date'];

  $smarty->assign('PRODUCTION_DATE',$production_date); //TODO normalisan megcsinalni
  $smarty->assign('PRODUCTION_START',date('Y')-30);
  $smarty->assign('PRODUCTION_END',date('Y'));
  $smarty->assign('EXPIRY_DATE',$expiry_date); //TODO normalisan megcsinalni
  $smarty->assign('EXPIRY_START',date('Y'));
  $smarty->assign('EXPIRY_END',date('Y')+10);

  /* stats and refs are collected via xml-rpc ??
  if($localItem) {
    $smarty->assign($repo->getStats($idObj));
    $smarty->assign('REFS', $repo->getRefs($idObj));
  }
  */

}
$page->send();

?>