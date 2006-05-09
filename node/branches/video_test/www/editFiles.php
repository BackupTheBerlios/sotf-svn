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
$videoconv=sotf_Utils::getParameter('videoconversion');
$convertall=sotf_Utils::getParameter('convertall');
$convertindex=sotf_Utils::getParameter('convertindex');
$createstills=sotf_Utils::getParameter('createstills');




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


if(!$prg->isLocal()) {
  raiseError("You can only edit programmes locally!");
}

if($prg->isVideoPrg()) $video=true;
else $video = false;
$converting=false;


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
if($new)$smarty->assign("NEW",1);

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
$prgAudiolist->getAudioVideoFromDir($prg->getAudioDir()); //CHANGED BY BUDDHAFLY

// check SQL validity
if($prgAudiolist->count() != count($mainAudio)) {
  $page->addStatusMsg("main_audio_count_mismatch");
}

$files = $prgAudiolist->getFiles();
debug('mainAudio', $mainAudio);
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



if($video){
	$still_found=sotf_VideoFile::searchForStill($prg);
	if (!$still_found && $createstills){
		sotf_VideoFile::createStills($files[0]->path, $files[0]->duration, $id);
	}
	
}


if($missing) {
  // there was a missing file description, so we have to restart the whole process
  $page->redirectSelf();
  exit;
}


$checker = & new sotf_ContentCheck($prgAudiolist); //todo $prgAudioList MEANT CONTENT
$checker = $checker->selectType();

//check for recently converted files or transcoding in progress
if($video && $prgAudiolist->count()){

	$list_changed=sotf_VideoFile::processTranscodingQueue($repository, $prg, $checker);
		
	if($list_changed) {
	  $page->redirectSelf();
	  exit;
	}
}


	
// compare with required formats	
	
	$PRG_AUDIO = array();
	
	
	for ($i=0;$i<count($config[$checker->prefix.'Formats']);$i++) // is either "audioFormats" or "videoFormats"
	{
	  $PRG_AUDIO[$i] = array("format" => $checker->getFormatFileName($i),
							 "index" => $i);
	  if ($checker->reqs[$i][0]) {
		$fname = $prgAudiolist->list[$checker->reqs[$i][1]]->name;
		$PRG_AUDIO[$i] = array_merge($PRG_AUDIO[$i], $mainAudio[$fname]);
		//$PRG_AUDIO[$i]['name'] = $fname;
		unset($mainAudio[$fname]);
	  } else {
	  	
		if($video){
			// if conversion in progress calculate percentage
			$regexp_file="/^".$id . '_.*_' . $checker->getFormatFilename($i)."$/";
			$source = $prgAudiolist->list[$checker->reqs[$i][1]]->getPath();
			$temppath = $config['wwwdir']."/tmp/";
			
			if ($tempdir = opendir($temppath)) {
			   while (false !== ($filename = readdir($tempdir))) {
					if(preg_match($regexp_file, $filename)){
						$PRG_AUDIO[$i]['converting']=true;
						$totalframes=$checker->getTotalFrames($source, $i);
						$perc_error = $checker->getPercentageOrError($temppath.$filename, $totalframes);
						$PRG_AUDIO[$i]['errors']=$perc_error['errors'];
						if($perc_error['percentage'])$PRG_AUDIO[$i]['percentage']="~ ".$perc_error['percentage']."%";
						if(!empty($perc_error['errors'])) $PRG_AUDIO[$i]['converting'] = false;
				   }
			   }
			   closedir($tempdir);
			}
		}
				
		$PRG_AUDIO[$i]['missing'] = 1;
		 $missing = 1;
	  }
	}
	
	
	//check whether a file is converting
	$converting=false;
	for ($i=0;$i<count($config[$checker->prefix.'Formats']);$i++){
		if($PRG_AUDIO[$i]['converting']==true) $converting=true;	
	}
	

	debug("mainAudio", $mainAudio);
	if(is_array($mainAudio)) {
	  while(list($fn,$finfo) = each($mainAudio)) {
		 $PRG_AUDIO[] = $finfo;
	  }
	}



// start converting required formats
if($videoconv && $missing){

	$obj = $repository->getObject($id);
	if(!$obj) raiseError("object does not exist!");

	checkPerm($obj->id, 'change');
	
	$checker->console = false;
	
	if($convertall) {
	  $checker->convertAll($obj->id);
	} 
	elseif($convertindex!="") {
	  $checker->convert($obj->id, $convertindex);
	}
	
	$page->redirect("editFiles.php?id=$id");
	exit;
}

//////////////////////////////////////////////////////////

$smarty->assign('CREATESTILLS', $createstills);

$smarty->assign('STILL_FOUND', $still_found);

$smarty->assign('VIDEO',$video);

$smarty->assign('CONVERTING',$converting);

$smarty->assign('MISSING',$missing);

$smarty->assign('MAIN_AUDIO_COUNT', $prgAudiolist->count());

$smarty->assign('PRG_AUDIO',$PRG_AUDIO);

$smarty->assign("USERFILES",$user->getUserFiles());

$smarty->assign('PRG_ID',$id);

$page->send();

?>
