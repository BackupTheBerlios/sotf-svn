<?php
require("init.inc.php");

// todo id must be  sql safe
sotf_Utils::registerGlobalParameters('id', 'popup');

$smarty->assign("PAGETITLE", $page->getlocalized("get"));
$smarty->assign("OKURL", $_SERVER['PHP_SELF'] . "?id=" . rawurlencode($id));
if($id) {
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

  $smarty->assign('REFERENCES', $prg->getRefs());
  /* stats and refs are collected via xml-rpc ??
  if($localItem) {
    $smarty->assign($repo->getStats($idObj));
    $smarty->assign('REFS', $repo->getRefs($idObj));
  }
  */

}

if(sotf_Utils::getParameter('popup')) {
  $smarty->assign('POPUP', 1);
  $page->sendPopup();
} else {
  $page->send();
}

?>