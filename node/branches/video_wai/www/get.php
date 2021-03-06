<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id: get.php 577 2006-05-29 12:04:06Z buddhafly $
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: Andr�s Micsik, M�t� Pataki, Tam�s D�ri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */



require("init.inc.php");
$starttime=mtime();

//$smarty->assign("OKURL", $_SERVER['PHP_SELF'] . "?id=" . rawurlencode($id));
$id = sotf_Utils::getParameter('id');
if($id) {

  $db->begin();

  $smarty->assign('ID', $id);

  $prg = &$repository->getObject($id);
  if(!$prg)
	 raiseError("no_such_object", $id);

  if(!$prg->getBool('published')) {
	 if(!hasPerm($prg->id, 'change')) {
		raiseError("not_published_yet", $id);
		exit;
	 }
	 $smarty->assign("UNPUBLISHED", 1);
  }

  $page->setTitle($prg->get('title'));

  // general data
  $prgData = $prg->getAll();
  $prgData['icon'] = sotf_Blob::cacheIcon($id);
  $smarty->assign('PRG_DATA', $prgData);
  // station data
  $station = $prg->getStation();
  $smarty->assign('STATION_DATA', $station->getAllWithIcon());
  // series data
  $series = $prg->getSeries();
  if($series) {
    $smarty->assign('SERIES_DATA', $series->getAllWithIcon());
  }

  // roles and contacts
  $smarty->assign('ROLES', $prg->getRoles());

  // genre
  $smarty->assign('GENRE', $vocabularies->getGenreName($prg->get('genre_id')));

  // topics
  //$smarty->assign('TOPICS', $prg->getTopics());	CP 201106: commented out because if active no topics are shown

  // language
  $smarty->assign('LANGUAGE', $prg->getLanguagesLocalized());
  // rights sections
  $smarty->assign('RIGHTS', $prg->getAssociatedObjects('sotf_rights', 'start_time'));



//check for recently converted files or transcoding in progress

if($prg->isVideoPrg()){
  sotf_VideoFile::processTranscodingQueue($repository, $prg);
}

  // content files 
  $mainContentFiles = $prg->getAssociatedObjects('sotf_media_files', 'main_content DESC, filename');
  $to = count($mainContentFiles);
  $flv_found = false; //ADDED BY Martin Schmidt
  for($i=0; $i<$to; $i++) {
	 if($prg->isLocal()) {
		// if local, we check if file disappeared in the meantime
		$path = $prg->getFilePath($mainContentFiles[$i]);
		if(!is_readable($path)) {
		  debug("DISAPPEARED FILE", $path);
		  unset($mainContentFiles[$i]);
		  continue;
		}
	 }
    $mainContentFiles[$i] =  array_merge($mainContentFiles[$i], sotf_AudioFile::decodeFormatFilename($mainContentFiles[$i]['format']));
	
	
	//ADDED BY Martin Schmidt
	//print_r($mainContentFiles[$i]);
	  if ($prg->isVideoPrg() && $mainContentFiles[$i]['format']=="flv" && $mainContentFiles[$i]['download_access']=='t'){
	  	$flv_path = sotf_Node::getHomeNodeRootUrl($prg) . '/getFile.php/' . 'fid__' . $mainContentFiles[$i]['id']. '__' . $fname.".flv";
		$flv_found= true;
		//$_SESSION['flv_path'] = $flv_path;
		
	  }
	  
	  $smarty->assign('FLV_PATH', $flv_path);
	///////////////////// 
	
	//ADDED BY Klaus Temper
	//print_r($mainContentFiles[$i]);
	  if ($mainContentFiles[$i]['format']=="mp3" && $mainContentFiles[$i]['download_access']=='t'){
	  	$mp3_path = sotf_Node::getHomeNodeRootUrl($prg) . '/getFile.php/' . $mainContentFiles[$i]['filename'] . '?audio=1&id='. $mainContentFiles[$i]['prog_id'] .'&filename=' . $mainContentFiles[$i]['filename'];
		$mp3_found= true;
				
	  }
	  
	  $smarty->assign('mp3_PATH', $mp3_path);
	///////////////////// 
	
	$d = getdate($mainContentFiles[$i]['play_length']);
	$d['hours']--;
	$mainContentFiles[$i]['playtime_string'] = ($d['hours'] ? $d['hours'].':' : '') . sprintf('%02d',$d['minutes']) . ':' . sprintf('%02d',$d['seconds']);
  }
  
  $smarty->assign('FLV_FOUND', $flv_found);
  
  $smarty->assign('AUDIO_FILES', $mainContentFiles);
  
  if($prg->isVideoPrg())$smarty->assign('VIDEO_PRG', 'true');
  

  // other files
  $otherFiles = $prg->getAssociatedObjects('sotf_other_files', 'filename');
  
  
  //select stills from other files
  $stills=array();
  for($k=count($otherFiles)-1;$k>=0;$k--){
  	if(preg_match('/^still_'.$id.'_[12345].gif$/', $otherFiles[$k]['filename'])){
			array_push($stills, $otherFiles[$k]);
	}
  }
  $stills=array_reverse($stills);
  //

  $noneAccessible = true;
  for($i=0;$i<count($otherFiles);$i++){
  	if($otherFiles[$i]['public_access']=='t') $noneAccessible = false;
  }
  if($noneAccessible) unset($otherFiles);
  
  $smarty->assign('OTHER_FILES', $otherFiles);
  $smarty->assign('STILLS', $stills);
  
  // links
  $smarty->assign('LINKS', $prg->getAssociatedObjects('sotf_links', 'caption'));

  // referencing portals
  $smarty->assign('REFS', $prg->getRefs());

  // statistics
  $smarty->assign('STATS', $prg->getStats());

  // add this visit to statistics
  $prg->addStat('', "visits");

  // rating
  $rating = new sotf_Rating();
  $smarty->assign('RATING', $rating->getInstantRating($id));

  // my rating?
  $myRating = $rating->getMyRating($id);
  debug("r", $myRating);
  $smarty->assign('MY_RATING', $myRating);

  if ($page->loggedIn()) {
    // is in my playlist?
    $smarty->assign('inplaylist', sotf_UserPlaylist::contains($id));
  }
}

$db->commit();

if(sotf_Utils::getParameter('popup')) {
  $smarty->assign('POPUP', 1);
  $page->sendPopup();
} else {
  //print "GESAMT: ".sprintf("%.05f",ntime($starttime,mtime()))." sec<br>";
  $page->send();
  
}


?>