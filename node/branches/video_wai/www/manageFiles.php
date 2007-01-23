<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id: manageFiles.php 409 2005-09-08 10:22:11Z xir $
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */


require("init.inc.php");

$smarty->assign('PAGETITLE',$page->getlocalized('manage_files'));

$page->popup = true;

$page->forceLogin();

// upload to my files
$upload = sotf_Utils::getParameter('upload');

//-------- mod by buddhafly/wolfi_fhstp 05-08-31
if($upload) {
  $userDir =  $user->getUserDir() . '/';
  $filename=$_FILES['userfile']['name'];
  $extension = substr($filename, strrpos($filename, '.') +1);
  $restname = substr($filename, 0, (-1*(strlen($extension)+1)));
  $newname = convert_special_chars(utf8_decode($restname)); //UTF-Module for PHP REQUIRED!!!
  $file = $userDir . $newname . "." . $extension;

  moveUploadedFile('userfile',  $file);
  $page->redirect("manageFiles.php");
  exit;
}
//---------

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

$smarty->assign("USERFTPURL", $user->getUrlForUserFTP()); 

$page->sendPopup();

?>
