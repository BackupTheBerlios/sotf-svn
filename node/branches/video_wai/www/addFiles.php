<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id: addFiles.php 272 2003-07-29 09:13:43Z andras $
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

$smarty->assign('PAGETITLE',$page->getlocalized('add_files'));

$page->forceLogin();

$prgId = sotf_Utils::getParameter('prgid');
$main = sotf_Utils::getParameter('main');
$add = sotf_Utils::getParameter('add');

if(empty($prgId))
     raiseError("Missing programme id!");

checkPerm($prgId, "change");

// upload file
$upload = sotf_Utils::getParameter('upload');
if($upload) {
  $fname = $_FILES['userfile']['name'];
  debug("_FILES", $_FILES['userfile']);
  $file = $user->getUserDir() . '/' . $fname;
  moveUploadedFile('userfile',  $file);
  $prg = new sotf_Programme($prgId);
  if($main) {
    $prg->setAudio($file);
    $page->redirect("closeAndRefresh.php");
  } else {
     $prg->setOtherFile($file);
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
      $file =  sotf_Utils::getFileInDir($user->getUserDir(), $fname);
      if($main) {
        debug("setAudio", "'$fname', '$copy'");
        $prg->setAudio($file, $copy);
      } else {
        debug("setOtherFile", "'$fname', '$copy'");
        $prg->setOtherFile($file, $copy);
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

$smarty->assign("USERFTPURL", $user->getUrlForUserFTP()); 

$page->sendPopup();

?>
