<?php

require("init.inc.php");

$smarty->assign('PAGETITLE',$page->getlocalized('add_files'));

$page->forceLogin();

$prgId = sotf_Utils::getParameter('prgid');
$main = sotf_Utils::getParameter('main');
$add = sotf_Utils::getParameter('add');

if(empty($prgId))
     raiseError("Missing programme id!");

if (!hasPerm($prgId, "change")) {
  raiseError("You have no permission to add files here!");
}

// upload file
$upload = sotf_Utils::getParameter('upload');
if($upload) {
  $fname = $_FILES['userfile']['name'];
  $file =  sotf_Utils::getFileInDir($user->getUserDir(), $fname);
  move_uploaded_file($_FILES['userfile']['tmp_name'], $file);
  $prg = new sotf_Programme($prgId);
  if($main) {
    $prg->setAudio($file);
    $page->redirect("closeAndRefresh.php");
  } else {
     $prg->setOtherFile($fname);
     $page->redirect("closeAndRefresh.php#anchor=mfiles");
  }
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
      if($main) {
        debug("setAudio", "'$fname', '$copy'");
        $prg->setAudio($user->getUserDir() . '/' . $fname, $copy);
      } else {
        debug("setOtherFile", "'$fname', '$copy'");
        $prg->setOtherFile($fname, $copy);
      }
    }
  }
  if($main)
    $page->redirect("closeAndRefresh.php");
  else 
    $page->redirect("closeAndRefresh.php#anchor=mfiles");
  exit;
}

// generate output

$smarty->assign('PRG_ID',$prgId);
$smarty->assign('MAIN',$main);


// general data
$smarty->assign('USERFILES',$user->getUserFiles());

$userFtpUrl = str_replace('ftp://', "ftp://".$user->name."@", "$userFTP$userid");
$smarty->assign("USERFTPURL", $userFtpUrl); 

$page->sendPopup();

?>
