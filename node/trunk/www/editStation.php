<?php

require("init.inc.php");

$smarty->assign('PAGETITLE',$page->getlocalized('edit_station'));

$page->forceLogin();

$stationid = sotf_Utils::getParameter('stationid');
$page->errorURL = "editStation.php?stationid=$stationid";

$st = & new sotf_Station($stationid);
$smarty->assign('STATION_ID',$stationid);
$smarty->assign('STATION',$st->get('name'));

if(!$st->isLocal()) {
  raiseError("You can only edit local stations!");
}
if (!hasPerm($st->id, "change")) {
  raiseError("You have no permission to change station settings!");
}

// save general data
$save = sotf_Utils::getParameter('save');
if($save) {
  $desc = sotf_Utils::getParameter('desc');
  $st->set('description', $desc);
  $st->update();
  $page->redirect("editStation.php?stationid=$stationid");
  exit;
}

// manage roles
$delrole = sotf_Utils::getParameter('delrole');
if($delrole) {
  $roleid = sotf_Utils::getParameter('roleid');
  $role = new sotf_NodeObject('sotf_object_roles', $roleid);
  $c = new sotf_Contact($role->get('contact_id'));
  $role->delete();
  //$msg = $page->getlocalizedWithParams("deleted_contact", $c->get('name'));
  //$page->addStatusMsg($msg, false);
  $page->redirect("editStation.php?stationid=$stationid");
  exit;
}

// manage permissions
$delperm = sotf_Utils::getParameter('delperm');
if($delperm) {
  $username = sotf_Utils::getParameter('username');
  $userid = $user->getUserid($username);
  if(empty($userid) || !is_numeric($userid)) {
    raiseError("Invalid username: $username");
  }
  $permissions->delPermission($st->id, $userid);
  $msg = $page->getlocalizedWithParams("deleted_permissions_for", $username);
  $page->addStatusMsg($msg, false);
  $page->redirect("editStation.php?stationid=$stationid");
  exit;
}

// icon and jingle

// delete jingle
$deljingle = sotf_Utils::getParameter('deljingle');
$jingleIndex = sotf_Utils::getParameter('index');
$jingleFile = sotf_Utils::getParameter('filename');
if($deljingle) {
  $st->deleteJingle($jingleFile, $jingleIndex);
  $page->redirect("editStation.php?stationid=$stationid#icon");
  exit;
}

// upload icon
$uploadicon = sotf_Utils::getParameter('uploadicon');
if($uploadicon) {
  $file =  sotf_Utils::getFileInDir($user->getUserDir(),$_FILES['userfile']['name']);
  move_uploaded_file($_FILES['userfile']['tmp_name'], $file);
  $st->setIcon($file);
  $page->redirect("editStation.php?stationid=$stationid#icon");
  exit;
}

// upload jingle
$uploadjingle = sotf_Utils::getParameter('uploadjingle');
if($uploadjingle) {
  $file =  sotf_Utils::getFileInDir($user->getUserDir(),$_FILES['userfile']['name']);
  move_uploaded_file($_FILES['userfile']['tmp_name'], $file);
  $st->setJingle($file);
  $page->redirect("editStation.php?stationid=$stationid#icon");
  exit;
}

// select icon/jingle from user files
$filename = sotf_Utils::getParameter('filename');
$setjingle = sotf_Utils::getParameter('setjingle');
$seticon = sotf_Utils::getParameter('seticon');
if($setjingle)
{
  $file =  sotf_Utils::getFileInDir($user->getUserDir(), $filename);
  $st->setJingle($file);
  $page->redirect("editStation.php?stationid=$stationid#icon");
}
elseif($seticon)
{
  $file =  sotf_Utils::getFileInDir($user->getUserDir(), $filename);
  //debug("FILE", $file);
  if ($st->setIcon($file)) {
    //$page->addStatusMsg("ok_icon");
  } else {
    //$page->addStatusMsg("error_icon");
  }
  $page->redirect("editStation.php?stationid=$stationid#icon");
}

// generate output

// general data
$smarty->assign('STATION_DATA',$st->data);
$smarty->assign('STATION_MANAGER',true);
$smarty->assign('ROLES', $st->getRoles());
$smarty->assign('SERIES', $st->listSeriesData());

// user permissions: editors and managers
$smarty->assign('PERMISSIONS', $permissions->listUsersAndPermissionsLocalized($st->id));

// icon and jingle
$smarty->assign('USERFILES',$user->getUserFiles());

if ($st->getIcon()) {
  $smarty->assign('ICON','1');
}

$jinglelist = & new sotf_FileList();
$jinglelist->getAudioFromDir($st->getStationDir(), 'jingle_');

// now $jinglelist contains the jingles
$checker = & new sotf_AudioCheck($jinglelist);		// check $jinglelist

$JINGLE = array();
for ($i=0;$i<count($audioFormats);$i++)
{
  if ($checker->reqs[$i][0]) {
    $resmgs = $jinglelist->list[$checker->reqs[$i][1]]->name;
    $hasJingle = 1;
    $usedAudio[] = $resmgs;
  } else
    $resmgs = '';
  $JINGLE[] = array('index' => $i, 
                    'filename' => $resmgs,
                    'format' => $audioFormats[$i]['format'],
                    'bitrate' => $audioFormats[$i]['bitrate'],
                    'channels' => $audioFormats[$i]['channels'],
                    'samplerate' => $audioFormats[$i]['samplerate']);
}
$jfiles = $jinglelist->getFiles();
for($i=0;$i<count($jfiles);$i++) {
  if(!in_array($jfiles[$i]->name, $usedAudio)) {
    $hasJingle = 1;
    $JINGLE[] = array( 'filename' => $jfiles[$i]->name,
                       'format' => $jfiles[$i]->format,
                       'bitrate' => $jfiles[$i]->bitrate,
                       'channels' => $jfiles[$i]->channels,
                       'samplerate' => $jfiles[$i]->samplerate);
  }
}


$smarty->assign('JINGLE',$JINGLE);
$smarty->assign('HAS_JINGLE',$hasJingle);

//$smarty->assign('OKURL',$PHP_SELF . '?station=' . rawurlencode($station));

$page->send();

?>
