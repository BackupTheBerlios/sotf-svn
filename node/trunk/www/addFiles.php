<?php

require("init.inc.php");

$smarty->assign('PAGETITLE',$page->getlocalized('add_files'));

$page->forceLogin();

$prgId = sotf_Utils::getParameter('prgid');
$add = sotf_Utils::getParameter('add');

if(empty($prgId))
     raiseError("Missing programme id!");

if (!hasPerm($prgId, "change")) {
  raiseError("You have no permission to add files here!");
}

$smarty->assign('PRG_ID',$prgId);


// upload to my files
$upload = sotf_Utils::getParameter('upload');
if($upload) {
  move_uploaded_file($_FILES['userfile']['tmp_name'], $user->getUserDir() . '/' . $_FILES['userfile']['name']);
  $page->redirect("editStation.php?stationid=$stationid#logo");
  exit;
}

// save general data
if($save) {
  $st->set('description', $desc);
  $st->update();
  $page->redirect("editStation.php?stationid=$stationid");
  exit;
}

// generate output

// general data
$smarty->assign('USERFILES',$user->getUserFiles());


$page->sendPopup();

?>
