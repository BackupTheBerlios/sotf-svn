<?php

require("init.inc.php");

$smarty->assign('PAGETITLE',$page->getlocalized('edit_station'));

$page->forceLogin();

$stationid = sotf_Utils::getParameter('stationid');
$save = sotf_Utils::getParameter('save');
$delperm = sotf_Utils::getParameter('delperm');
$delrole = sotf_Utils::getParameter('delrole');
$view = sotf_Utils::getParameter('view');
$setjingle = sotf_Utils::getParameter('setjingle');
$setlogo = sotf_Utils::getParameter('setlogo');
$filename = sotf_Utils::getParameter('filename');
$roleid = sotf_Utils::getParameter('roleid');
$desc = sotf_Utils::getParameter('desc');

$path_parts = pathinfo(realpath($filename));
$filename = $path_parts['basename'];

$st = & new sotf_Station($stationid);
$smarty->assign('STATION_ID',$stationid);
$smarty->assign('STATION',$st->get('name'));

if(!$st->isLocal()) {
  raiseError("You can only edit local stations!");
}
if (!hasPerm($st->id, "change")) {
  raiseError("You have no permission to change station settings!");
}

// upload to my files
$upload = sotf_Utils::getParameter('upload');
if($upload) {
  move_uploaded_file($_FILES['userfile']['tmp_name'], $user->getUserDir() . '/' . $_FILES['userfile']['name']);
  $page->redirect("editStation.php?stationid=$stationid#logo");
  exit;
}

// save general data
if($save) {
  $st->set('description', $desc);
  $st->update();
  $page->redirect("editStation.php?stationid=$stationid");
  exit;
}

// manage roles
if($delrole) {
  $role = new sotf_NodeObject('sotf_object_roles', $roleid);
  $c = new sotf_Contact($role->get('contact_id'));
  $role->delete();
  $msg = $page->getlocalizedWithParams("deleted_contact", $c->get('name'));
  $page->addStatusMsg($msg, false);
  $page->redirect("editStation.php?stationid=$stationid");
  exit;
}

// manage permissions
if($delperm) {
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
if($setjingle)
{
  $audiofile = & new sotf_AudioFile($user->getUserDir() . '/' . $filename);
  if ($st->setJingle($audiofile))
    $page->addStatusMsg("ok_jingle");
  else
    $page->addStatusMsg("error_jingle");
  $page->redirect("editStation.php?stationid=$stationid#logo");
}
elseif($setlogo)
{
//  $file = & new sotf_File($user->getUserDir().'/'.$filename);
  $oldfile = $user->getUserDir().'/'.$filename;
  $newfile = $user->getUserDir().'/'.time().".img";
  if (!sotf_Utils::resizeImage($oldfile, $newfile, $iconWidth, $iconHeight))
    $page->addStatusMsg("error_logo");
  else
  {
	  $file = & new sotf_File($newfile);
	  if ($st->setLogo($file))
	    $page->addStatusMsg("ok_logo");
	  else
	    $page->addStatusMsg("error_logo");
  }
  if(is_file($newfile))
    unlink($newfile);
  $page->redirect("editStation.php?stationid=$stationid#logo");
}

// generate output

// general data
$smarty->assign('STATION_DATA',$st->data);
$smarty->assign('STATION_MANAGER',true);
$smarty->assign('ROLES', $st->getRoles());

// user permissions: editors and managers
$smarty->assign('PERMISSIONS', $permissions->listUsersAndPermissionsLocalized($st->id));

// logo and jingle
$smarty->assign('USERFILES',$user->getUserFiles());

if ($st->getLogo()) {
  $smarty->assign('LOGO','1');
}

$jinglelist = & new sotf_FileList();
$jinglelist->getAudioFromDir($st->getStationDir());
$dellist = array();		// stores files to remove from $jinglelist
for ($i=0; $i<count($jinglelist->list); $i++ ) {
  if (substr($jinglelist->list[$i]->name,0,6) != "jingle")
    $dellist[] = $jinglelist->list[$i]->getPath();
}
for ($i=0;$i<count($dellist);$i++) {
  $jinglelist->remove($dellist[$i]);
}

// now $jinglelist contains the jingles
$checker = & new sotf_AudioCheck($jinglelist);		// check $jinglelist

$JINGLE = array();
for ($i=0;$i<count($audioFormats);$i++)
{
  if ($checker->reqs[$i][0])
    $resmgs = '<a href="getJingle.php/' . $jinglelist->list[$checker->reqs[$i][1]]->name . '?station='.rawurlencode($station).'&index=' . $i . '">' . $jinglelist->list[$checker->reqs[$i][1]]->name . '</a>';
  else
    $resmgs = '<font color="red">' . $page->getlocalized("missing") . '</font>';
  $JINGLE[] = array($resmgs,$audioFormats[$i]['format'],$audioFormats[$i]['bitrate'],$audioFormats[$i]['channels'],$audioFormats[$i]['samplerate']);
}
$smarty->assign('JINGLE',$JINGLE);

$smarty->assign('OKURL',$PHP_SELF . '?station=' . rawurlencode($station));

$page->send();

?>
