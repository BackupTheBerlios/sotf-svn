<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

$id = sotf_Utils::getParameter('id');
$new = sotf_Utils::getParameter('new');
$okURL = sotf_Utils::getParameter('okURL');

if($new) {
  $smarty->assign("PAGETITLE", $page->getlocalized("New_prog_step1"));
} else {
  $smarty->assign("PAGETITLE", $page->getlocalized("Edit_files"));
}
    
$page->forceLogin();

// set caption
$capid =  sotf_Utils::getParameter('capid');
$capvalue =  sotf_Utils::getParameter('capvalue');
$capurl =  sotf_Utils::getParameter('capurl');
$capname =  sotf_Utils::getParameter('capname');
if ($capname == "ofiles")
{
	$x = new sotf_NodeObject("sotf_other_files", $capid);
	$x->set('caption', addslashes($capvalue));
	$x->update();
	if (!strstr($capurl, "#")) $capurl .= "#ofiles";
	$page->redirect($capurl);
}
elseif ($capname == "mfiles")
{
	$x = new sotf_NodeObject("sotf_media_files", $capid);
	$x->set('caption', addslashes($capvalue));
	$x->update();
	if (!strstr($capurl, "#")) $capurl .= "#mfiles";
	$page->redirect($capurl);
}

$prg = & new sotf_Programme($id);

// admins or owners can change files
checkPerm($id, 'change');

// delete link
$delLink = sotf_Utils::getParameter('dellink');
$linkid = sotf_Utils::getParameter('linkid');
if($delLink) {
  $link = new sotf_NodeObject("sotf_links", $linkid);
  $link->delete();
  $page->redirect("editFiles.php?id=$id#links");
  exit;
}

// delete file
$delFile = sotf_Utils::getParameter('delfile');
if($delFile) {
  $prg->deleteFile($delFile);
  $page->redirect("editFiles.php?id=$id#mfiles");
  exit;
}

// generate output
//$smarty->assign("OKURL",$okURL);
if($new)
     $smarty->assign("NEW",1);

$smarty->assign('PRG_DATA', $prg->getAll());

$smarty->assign('LINKS', $prg->getAssociatedObjects('sotf_links', 'caption'));

// TODO: compare directory and SQL data for correctness

// other files
$otherFiles = $prg->listOtherFiles();
$smarty->assign('OTHER_FILES', $otherFiles);

// audio files which does not contain the main programme
$smarty->assign('AUDIO_FILES', $prg->listAudioFiles('false'));

// audio files for programme
$audioFiles = $prg->listAudioFiles('true');
for ($i=0;$i<count($audioFiles);$i++) {
    $mainAudio[$audioFiles[$i]['filename']] = $audioFiles[$i];
}

$prgAudiolist = & new sotf_FileList();
$prgAudiolist->getAudioFromDir($prg->getAudioDir());

// check SQL validity
if($prgAudiolist->count() != count($mainAudio)) {
  $page->addStatusMsg("main_audio_count_mismatch");
}

$files = $prgAudiolist->getFiles();
debug('mainAUdio', $mainAudio);
debug('prgaudiolist', $files);
for ($i=0;$i<count($files);$i++) {
  if(!$mainAudio[$files[$i]->name]) {
    // missing from SQL!
    $missing = 1;
    $prg->saveFileInfo($files[$i]->path, true);
    $msg = $page->getlocalizedWithParams("missing_from_sql", $files[$i]->name);
    $page->addStatusMsg($msg, false);
  }
  // TODO: check all fields
}

if($missing) {
  // there was a missing file description, so we have to restart the whole process
  $page->redirectSelf();
  exit;
}

// compare with required formats
$checker = & new sotf_AudioCheck($prgAudiolist);

$PRG_AUDIO = array();
for ($i=0;$i<count($audioFormats);$i++)
{
  $PRG_AUDIO[$i] = array("format" => $checker->getFormatFileName($i),
                         "index" => $i);
  if ($checker->reqs[$i][0]) {
    $fname = $prgAudiolist->list[$checker->reqs[$i][1]]->name;
    $PRG_AUDIO[$i] = array_merge($PRG_AUDIO[$i], $mainAudio[$fname]);
    //$PRG_AUDIO[$i]['name'] = $fname;
    unset($mainAudio[$fname]);
  } else {
    $PRG_AUDIO[$i]['missing'] = 1;
  }
}

debug("mainAudio", $mainAudio);
while(list($fn,$finfo) = each($mainAudio)) {
  $PRG_AUDIO[] = $finfo;
}

$smarty->assign('PRG_AUDIO',$PRG_AUDIO);

$smarty->assign("USERFILES",$user->getUserFiles());

$smarty->assign('PRG_ID',$id);

$page->send();

?>
