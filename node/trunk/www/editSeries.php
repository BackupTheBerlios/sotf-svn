<?php  // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */


require("init.inc.php");

$seriesid = sotf_Utils::getParameter('seriesid');
$page->errorURL = "editSeries.php?seriesid=$seriesid";
$page->setTitle('edit_series');
$page->popup = true;
$page->forceLogin();

if(!$seriesid) {
  raiseError("Id is missing");
}

$series = & new sotf_Series($seriesid);

checkPerm($series->id, "change", 'authorize');

// save general data
$save = sotf_Utils::getParameter('save');
$finish = sotf_Utils::getParameter('finish');
$finish2 = sotf_Utils::getParameter('finish2');
if($save || $finish) {
  checkPerm($series->id, "change");
  $series->setWithParam('name');
  $series->setWithParam('description');
  $series->update();
}
if($finish || $finish2) {
  $page->redirect("closeAndRefresh.php?anchor=series");
}
if($save) {
  $page->redirect("editSeries.php?seriesid=$seriesid");
}

// manage roles
$delrole = sotf_Utils::getParameter('delrole');
if($delrole) {
  checkPerm($series->id, "change");
  $roleid = sotf_Utils::getParameter('roleid');
  $role = new sotf_NodeObject('sotf_object_roles', $roleid);
  $c = new sotf_Contact($role->get('contact_id'));
  $role->delete();
  //$msg = $page->getlocalizedWithParams("deleted_contact", $c->get('name'));
  //$page->addStatusMsg($msg, false);
  $page->redirect("editSeries.php?seriesid=$seriesid#roles");
  exit;
}

// manage permissions
$delperm = sotf_Utils::getParameter('delperm');
if($delperm) {
  checkPerm($series->id, "authorize");
  $username = sotf_Utils::getParameter('username');
  $userid = $user->getUserid($username);
  if(empty($userid) || !is_numeric($userid)) {
    raiseError("Invalid username: $username");
  }
  $permissions->delPermission($series->id, $userid);
  $msg = $page->getlocalizedWithParams("deleted_permissions_for", $username);
  $page->addStatusMsg($msg, false);
  $page->redirect("editSeries.php?seriesid=$seriesid#perms");
  exit;
}

// icon and jingle

// delete jingle
$deljingle = sotf_Utils::getParameter('deljingle');
$jingleIndex = sotf_Utils::getParameter('index');
$jingleFile = sotf_Utils::getParameter('filename');
if($deljingle) {
  checkPerm($series->id, "change");
  $series->deleteJingle($jingleFile, $jingleIndex);
  $page->redirect("editSeries.php?seriesid=$seriesid#icon");
  exit;
}

// upload icon
$uploadIcon = sotf_Utils::getParameter('uploadicon');
if($uploadIcon) {
  checkPerm($series->id, "change");
  $file =  $user->getUserDir() . '/' . $_FILES['userfile']['name'];
  moveUploadedFile('userfile',  $file);
  if ($series->setIcon($file)) {
    //$page->addStatusMsg("ok_icon");
  } else {
    $page->addStatusMsg("error_icon");
  }
  $page->redirect("editSeries.php?seriesid=$seriesid#icon");
  exit;
}

// upload jingle
$uploadjingle = sotf_Utils::getParameter('uploadjingle');
if($uploadjingle) {
  checkPerm($series->id, "change");
  $file =  $user->getUserDir() . '/' . $_FILES['userfile']['name'];
  moveUploadedFile('userfile',  $file);
  $series->setJingle($file);
  $page->redirect("editSeries.php?seriesid=$seriesid#icon");
  exit;
}

// select icon/jingle from user files
$filename = sotf_Utils::getParameter('filename');
$setjingle = sotf_Utils::getParameter('setjingle');
$seticon = sotf_Utils::getParameter('seticon');
if($setjingle) {
  checkPerm($series->id, "change");
  $file =  sotf_Utils::getFileInDir($user->getUserDir(), $filename);
  $series->setJingle($file);
  $page->redirect("editSeries.php?seriesid=$seriesid#icon");
} elseif($seticon) {
  checkPerm($series->id, "change");
  $file =  sotf_Utils::getFileInDir($user->getUserDir(), $filename);
  //debug("FILE", $file);
  if ($series->setIcon($file)) {
    //$page->addStatusMsg("ok_icon");
  } else {
    //$page->addStatusMsg("error_icon");
  }
  $page->redirect("editSeries.php?seriesid=$seriesid#icon");
}



// generate output

// general data
$smarty->assign('SERIES_ID',$seriesid);
$smarty->assign('SERIES',$series->get('name'));

$smarty->assign('SERIES_DATA',$series->getAll());
$smarty->assign('SERIES_MANAGER',true);
$smarty->assign('ROLES', $series->getRoles());

// user permissions: editors and managers
$smarty->assign('PERMISSIONS', $permissions->listUsersAndPermissionsLocalized($series->id));

// upload
$smarty->assign('USERFILES',$user->getUserFiles());

// icon
if ($series->getIcon()) {
  $smarty->assign('ICON','1');
}

// jingle
$jinglelist = & new sotf_FileList();
$jinglelist->getAudioFromDir($series->getMetaDir(), 'jingle_');

// now $jinglelist contains the jingles
$checker = & new sotf_AudioCheck($jinglelist);		// check $jinglelist

$JINGLE = array();
for ($i=0;$i<count($config['audioFormats']);$i++)
{
  if ($checker->reqs[$i][0]) {
    $resmgs = $jinglelist->list[$checker->reqs[$i][1]]->name;
    $hasJingle = 1;
    $usedAudio[] = $resmgs;
  } else
    $resmgs = '';
  $JINGLE[] = array('index' => $i, 
                    'filename' => $resmgs,
                    'format' => $config['audioFormats'][$i]['format'],
                    'bitrate' => $config['audioFormats'][$i]['bitrate'],
                    'channels' => $config['audioFormats'][$i]['channels'],
                    'samplerate' => $config['audioFormats'][$i]['samplerate']);
}
$jfiles = $jinglelist->getFiles();
for($i=0;$i<count($jfiles);$i++) {
  if(!$usedAudio || !in_array($jfiles[$i]->name, $usedAudio)) {
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


$page->sendPopup();

?>
