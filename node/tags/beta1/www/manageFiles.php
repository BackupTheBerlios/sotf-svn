<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: Andr�s Micsik, M�t� Pataki, Tam�s D�ri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */


require("init.inc.php");

$smarty->assign('PAGETITLE',$page->getlocalized('manage_files'));

$page->popup = true;

$page->forceLogin();

// upload to my files
$upload = sotf_Utils::getParameter('upload');
if($upload) {
  $file =  $user->getUserDir() . '/' . $_FILES['userfile']['name'];
  moveUploadedFile('userfile',  $file);
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

$smarty->assign("USERFTPURL", $user->getUrlForUserFTP()); 

$page->sendPopup();

?>
