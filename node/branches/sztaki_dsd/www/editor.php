<?php //-*- tab-width: 3; indent-tabs-mode: 1; -*-
require("init.inc.php");

$smarty->assign("PAGETITLE", $page->getlocalized("EditorPage"));
$page->forceLogin();
$smarty->assign("OKURL", $_SERVER['PHP_SELF']);

if (!$user->isEditor()) {
	raiseError("You have no permission to upload to any station");
	exit;
}

if(sotf_Utils::getParameter('upload')) {
  move_uploaded_file($_FILES['userfile']['tmp_name'], $user->getUserDir() . '/' . $_FILES['userfile']['name']);
  $page->redirect($_SERVER['SCRIPT_NAME']);
  exit;
}

if(sotf_Utils::getParameter('addprog')) {
  $fname = sotf_Utils::getFileSafeParameter('fname');
  $station = sotf_Utils::getFileSafeParameter('station');
  if(!sotf_Permission::get('upload', $station)) {
    raiseError("no permission to upload to $station");
    exit;
  }
  $newPrg = sotf_Programme::create($station);
  $newPrg->setAudio($fname);
  //$page->redirect("editFiles.php");
  $page->redirect($_SERVER['SCRIPT_NAME']);
  exit;
}


$userFtpUrl = str_replace('ftp://', "ftp://$userid@", "$userFTP$userid");
	$smarty->assign("USERFTPURL", $userFtpUrl); 

$stations = sotf_Permission::listStationsWithPermission('upload');
if(!empty($stations)) {
     $smarty->assign_by_ref("STATIONS",$stations);
}

$userAudioFiles = new sotf_FileList();
$userAudioFiles->getAudioFromDir($user->getUserDir());
$list = $userAudioFiles->getFileNames();
if(!empty($list)) {
		 $smarty->assign_by_ref("USER_AUDIO_FILES", $list);
}

$myProgs = sotf_Programme::myProgrammes($user->name);
$plist = new sotf_PrgList($myProgs);
// todo sort/filter using sotf_PrgList
$l = $plist->getList();
$smarty->assign_by_ref("MYPROGS", $l);


$page->send();

?>
