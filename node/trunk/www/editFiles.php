<?php
require("init.inc.php");

if(sotf_Utils::getParameter('newProg')) {
  $smarty->assign("PAGETITLE", $page->getlocalized("New_prog_step1"));
} else {
  $smarty->assign("PAGETITLE", $page->getlocalized("Edit_files"));
}
    
$page->forceLogin();

sotf_Utils::registerGlobalParameters('id', 'okURL', 'copy');

$ok = sotf_Utils::getParameter('ok');
$okURL = sotf_Utils::getParameter('okURL');
$send = sotf_Utils::getParameter('send');
$selectedUserFiles = sotf_Utils::getParameter('userfiles');
$selectedOtherFiles = sotf_Utils::getParameter('otherfiles');
$delLink = sotf_Utils::getParameter('dellink');
$addLink = sotf_Utils::getParameter('addlink');
$delother = sotf_Utils::getParameter('delother');
$capid =  sotf_Utils::getParameter('capid');
$capvalue =  sotf_Utils::getParameter('capvalue');
$capurl =  sotf_Utils::getParameter('capurl');
$capname =  sotf_Utils::getParameter('capname');

$smarty->assign("OKURL",$okURL);

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
if(!hasPerm($id, 'change')) {
  raiseError("no permission to change files in this programme");
  exit;
}

if($delLink) {
  $link = new sotf_NodeObject("sotf_links", sotf_Utils::getParameter('linkid'));
  $link->delete();
  $page->redirect("editFiles.php?id=$id");
  exit;
}

$smarty->assign('LINKS', $prg->getAssociatedObjects('sotf_links', 'caption'));

// TODO: compare directory and SQL data for correctness

// other files
$otherFiles = $prg->getAssociatedObjects('sotf_other_files', 'filename');
$smarty->assign('OTHER_FILES', $otherFiles);

// audio files which does not contain the main programme
$mainAudio = array();
$audioFiles = $prg->getAssociatedObjects('sotf_media_files', 'main_content, filename');
for ($i=0;$i<count($audioFiles);$i++) {
  if($audioFiles[$i]['main_content']=='t') {
    $mainAudio[$audioFiles[$i]['filename']] = $audioFiles[$i];
    unset($audioFiles[$i]);
  }
}
$smarty->assign('AUDIO_FILES', $audioFiles);

// audio files for programme
$prgAudiolist = & new sotf_FileList();
$prgAudiolist->getAudioFromDir($prg->getAudioDir());

// check SQL validity
if($prgAudiolist->count() != count($mainAudio)) {
  $page->addStatusMsg("main_audio_count_mismatch");
}
 
$files = $prgAudiolist->getFiles();
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
  $PRG_AUDIO[] = array('name' => $fn,
                       'format' => $finfo['format']);
}

$smarty->assign('PRG_AUDIO',$PRG_AUDIO);

$smarty->assign("USERFILES",$user->getUserFiles());

$smarty->assign('PRG_ID',$id);

$page->send();

?>
