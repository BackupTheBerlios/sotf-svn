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
  $page->redirect("addFiles.php?prgid=$prgId");
  exit;
}

// add files
if($add) {
  $copy = sotf_Utils::getParameter('copy');
  $prg = new sotf_Programme($prgId);
  reset ($_POST);
  while(list($k,$fname) = each($_POST)) {
    debug("P", $k);
    if(substr($k, 0, 4) == 'sel_') {
      debug("adding", "'$fname', '$copy'");
      $prg->setOtherFile($fname, $copy);
    }
  }
  $page->redirect("closeAndRefresh.php");
  exit;
}

// generate output

// general data
$smarty->assign('USERFILES',$user->getUserFiles());

$userFtpUrl = str_replace('ftp://', "ftp://".$user->name."@", "$userFTP$userid");
$smarty->assign("USERFTPURL", $userFtpUrl); 

$page->sendPopup();

?>
