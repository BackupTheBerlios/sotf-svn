<?php //-*- tab-width: 3; indent-tabs-mode: 1; -*-
require("init.inc.php");

$smarty->assign("PAGETITLE", $page->getlocalized("EditorPage"));
$page->forceLogin();
$smarty->assign("OKURL", $_SERVER['PHP_SELF']);


if (!$permissions->isEditor()) {
	raiseError("You have no permission to upload to any station");
	exit;
}

if(sotf_Utils::getParameter('upload')) {
  move_uploaded_file($_FILES['userfile']['tmp_name'], $user->getUserDir() . '/' . $_FILES['userfile']['name']);
  $page->redirect($_SERVER['SCRIPT_NAME']);
  exit;
}

if(sotf_Utils::getParameter('addprog')) {
  $fname = sotf_Utils::getParameter('fname');
  $station = sotf_Utils::getParameter('station');
  if(!$permissions->hasPermission($station, 'add_prog')) {
    raiseError("no permission to upload to $station");
    exit;
  }
  $newPrg = new sotf_Programme();
  $track = preg_replace('/\.[^.]*$/','', $fname);
  debug("create with track", $track);
  $newPrg->create($station, $track);
  $newPrg->setAudio($user->getUserDir() . '/' . $fname);
  $permissions->addPermission($newPrg->id, $user->id, 'admin');
  //$page->redirect("editFiles.php");
  $page->redirect("editFiles.php?new=1&id=" . $newPrg->getID());
  exit;
}

$stationId = sotf_Utils::getParameter('stationid');
if($stationId)
	  $smarty->assign('SELECTED_STATION', $stationId);

$userFtpUrl = str_replace('ftp://', "ftp://$user->name@", $userFTP . $user->name);
	$smarty->assign("USERFTPURL", $userFtpUrl); 

$stations = $permissions->listStationsForEditor();
if(!empty($stations)) {
     $smarty->assign_by_ref("STATIONS",$stations);
}

$userAudioFiles = new sotf_FileList();
$userAudioFiles->getAudioFromDir($user->getUserDir());
$list = $userAudioFiles->getFileNames();
if(!empty($list)) {
		 $smarty->assign_by_ref("USER_AUDIO_FILES", $list);
}

//$max = $db->getAll("SELECT count(*) FROM (".$query.") as count");	//get the number of results
//$max = $max[0]["count"];
$max = 10;
$limit = $page->splitList($max, "");
//$result = $db->getAll($query.$limit["limit"]);

//var_dump($stationId);
//var_dump(sotf_Permission::mySeriesData($stationId));

$sortby[a] = "a";
$sortby[b] = "b";

$myProgs = sotf_Programme::myProgrammes("", "", "name");
//$plist = new sotf_PrgList($myProgs);
//// todo sort/filter using sotf_PrgList
//$l = $plist->getList();
$smarty->assign_by_ref("sortby", $sortby);
$smarty->assign_by_ref("MYPROGS", $myProgs);


$page->send();

?>
