<?php

require("init.inc.php");

$smarty->assign('PAGETITLE',$page->getlocalized('manage_files'));

$page->popup = true;

$page->forceLogin();

// upload to my files
$upload = sotf_Utils::getParameter('upload');
if($upload) {
  move_uploaded_file($_FILES['userfile']['tmp_name'], $user->getUserDir() . '/' . $_FILES['userfile']['name']);
  $page->redirect("manageFiles.php");
  exit;
}

// delete files
$del = sotf_Utils::getParameter('del');
if($del) {
  reset ($_POST);
  while(list($k,$fname) = each($_POST)) {
    debug("P", $k);
    if(substr($k, 0, 4) == 'sel_') {
      if(!unlink($user->getUserDir() . '/' . $fname)) {
        addError("Could not delete: $fname");
      }
    }
  }
  $page->redirect("manageFiles.php");
  exit;
}

// close
$close = sotf_Utils::getParameter('close');
if($close) {
  $page->redirect("closeAndRefresh.php");
  exit;
}

// generate output

$smarty->assign('USERFILES',$user->getUserFiles());

$userFtpUrl = str_replace('ftp://', "ftp://".$user->name."@", "$userFTP$userid");
$smarty->assign("USERFTPURL", $userFtpUrl); 

$page->sendPopup();

?>
