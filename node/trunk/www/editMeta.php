<?php  // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 *
 	@internal change "read ID3 Tags from MP3 File and write ID3 Tags from Metadata fields"
~  *           by wolfgang csacsinovits and martin schmidt (fh st. poelten)
~  * 
~  * 
~  */


require("init.inc.php");

$prgId = sotf_Utils::getParameter('id');
$new = sotf_Utils::getParameter('new');

if($new)
     $smarty->assign("PAGETITLE", $page->getlocalized("New_prog_step1"));
else
     $smarty->assign("PAGETITLE", $page->getlocalized("editmeta"));
$page->forceLogin();

$okURL = sotf_Utils::getParameter('okURL');

// delete topic
$delTopic = sotf_Utils::getParameter('deltopic');
if($delTopic) {
  $vocabularies->delFromTopic($delTopic);
  $page->redirect("editMeta.php?id=$prgId#topics");
  exit;
}

$prg = & new sotf_Programme($prgId);


// ---- changed by wolfgang csacsinovits and martin schmidt GET ID3 TAGS and write into Metadata - Fields


	$audioFiles = $prg->listAudioFiles('true');
	
	$file =  $prg->getAudioDir() . '/' . $audioFiles[0] ['filename'];

	$ThisFileInfo = GetAllFileInfo($file, 'mp3', false, false, false) ;
	
	
	if (($ThisFileInfo['comments']['title']) != '' ) {
		$title = implode(', ', $ThisFileInfo['comments']['title']);
		}
		
	if (($ThisFileInfo['comments']['artist']) != '' ) {	
		$artist = implode(', ', $ThisFileInfo['comments']['artist']);
		}
	if (($ThisFileInfo['comments']['genre']) != '' ) {	
		$genre = implode(', ', $ThisFileInfo['comments']['genre']);
		}
	
	if (($ThisFileInfo['comments']['album']) != '' ) {	
		$album = implode(', ', $ThisFileInfo['comments']['album']);
		}
	
		$keywords = $artist;
		if ($title != '') $keywords .= ', ' . $title;
		if ($genre != '' && $genre!='UNKNOWN_GENRE') $keywords .= ', ' . $genre;

		$prg->set('title' , $title);
		$prg->set('keywords' ,$keywords);
// -------------------------------------------------------------------------------------



if(!$prg->isLocal()) {
  raiseError("You can only edit programmes locally!");
}

checkPerm($prg, 'change', 'authorize');

if(sotf_Utils::getParameter('delfromseries')) {
  checkPerm($prg, 'change');
  $prg->set("series_id", null);
  $prg->update();
  $page->redirect("editMeta.php?id=$prgId");
}

$finishpublish = sotf_Utils::getParameter('finishpublish');
$finish = sotf_Utils::getParameter('finish');
$save = sotf_Utils::getParameter('save');
if($save || $finish || $finishpublish) {
  checkPerm($prg, 'change');
  $params = array('title'=>array('text',1),
                  'alternative_title'=>array('text',0),
                  'episode_title'=>array('text',0),
                  'episode_sequence'=>array('number',0),
                  'keywords'=>array('text',1),
                  'abstract'=>array('text',1),
                  'genre_id'=>array('number',1),
                  'spatial_coverage'=>array('text',0),
                  'temporal_coverage'=>array('date',0),
                  'production_date'=>array('date',0),
                  'broadcast_date'=>array('date',0),
                  'expiry_date'=>array('date',0)
                  );
  
 // changed by wolfgang csacsinovits and martin schmidt -> check empty fields, validate input data
  $field_error = array();
  $error_count = 0;
  foreach($params as $param=>$type) {
  
    $value = sotf_Utils::getParameter($param);
	
	if($type[1]== 1 && ($value == '' || $value == 'untitled')) {
	$field_error[$param]=true;
	$error_count++;
	}
	else $field_error[$param]=false;
	
	if($param=='genre_id' && $value==0) {
	$field_error[$param]=true;
	$error_count++;
	}
	
    if($type[0]=='text') {
      $value = strip_tags($value);
    } elseif($type[0]=='number') {
      if(empty($value))
        $value = 0;
      elseif(!is_numeric($value)) {
        addError($page->getlocalized('not_a_number') . ": $value");
        continue;
      }
    } elseif($type[0]=='date') {
      if (sotf_Utils::getParameter($param . '_radio1') != "unselected") {
	      $value = sotf_Utils::getParameter($param . 'Year') . '-'
	        . sotf_Utils::getParameter($param . 'Month') . '-'
	        . sotf_Utils::getParameter($param . 'Day');
			if($param == 'broadcast_date') {
				$value = $value . ' ' . sotf_Utils::getParameter('broadcast_dateHour') . ':' . sotf_Utils::getParameter('broadcast_dateMinute'); // . ' ' . $db->myTZ();
			}
		} else {
		  $value = NULL;
		}
    }
	
	
    $prg->set($param, $value);
	 
	
  }



//echo $error_msg;

  // language hack
  $prg->setLanguageWithParams();
  
  
	// changed by wolfgang csacsinovits and martin schmidt - WRITE ID3 - TAGS --------------------------------------------------
	
	$audioFiles = $prg->listAudioFiles('true');
	for($q = 0; $q < count($audioFiles); $q++) {
	
		$file =  $prg->getAudioDir() . '/' . $audioFiles[$q] ['filename'];
		
		// check if file is mp3 - file
		$filename = $file;
		$extension = substr($filename, strrpos($filename, '.') +1);
		
		$productiondate = (SafeStripSlashes(sotf_Utils::getParameter('production_date')));
		
		if($extension=="mp3") {
		
		
		// TITLE
		$data['id3v2']['TIT2']['encodingid'] = 0;
		$data['id3v2']['TIT2']['data']       = SafeStripSlashes(sotf_Utils::getParameter('title'));
		$data['ape']['title'][] = SafeStripSlashes(sotf_Utils::getParameter('title'));
		
		$data['id3v1']['TIT2']['encodingid'] = 0;
		$data['id3v1']['TIT2']['data']       = SafeStripSlashes(sotf_Utils::getParameter('title'));
		$data['ape']['title'][] = SafeStripSlashes(sotf_Utils::getParameter('title'));
		$title =  $data['ape']['title'][0];

		
		// STATION
		
		$prg->station = $prg->getObject($prg->get('station_id'));
		if(is_object($prg->station)) {
			$prg->stationName = $prg->station->get('name');
			$station_name = $prg->stationName;
		}
		else $station_name = "";
		
		$data['id3v2']['TPE1']['encodingid'] = 0;
		$data['id3v2']['TPE1']['data']       = SafeStripSlashes($station_name);
		$data['ape']['artist'][] = SafeStripSlashes($station_name);
		
		$data['id3v1']['TPE1']['encodingid'] = 0;
		$data['id3v1']['TPE1']['data']       = SafeStripSlashes($station_name);
		$data['ape']['artist'][] = SafeStripSlashes($station_name);
		$artist = $data['ape']['artist'][0];

		
		// YEAR
		$data['id3v2']['TYER']['encodingid'] = 0;
		$data['id3v2']['TYER']['data']       = SafeStripSlashes(sotf_Utils::getParameter('production_date' . 'Year'));//
		$data['ape']['year'][] = SafeStripSlashes(sotf_Utils::getParameter('production_date' . 'Year'));//
		
		$data['id3v1']['TYER']['encodingid'] = 0;
		$data['id3v1']['TYER']['data']       = SafeStripSlashes(sotf_Utils::getParameter('production_date' . 'Year'));//
		$data['ape']['year'][] = SafeStripSlashes(sotf_Utils::getParameter('production_date' . 'Year'));//
		$year = $data['ape']['year'][0]; 

		
		// GENRE
		$data['id3v2']['TCON']['encodingid'] = 0;
		$data['id3v2']['TCON']['data']       = SafeStripSlashes($vocabularies->getGenreName($prg->get('genre_id')));
		$data['ape']['genre'][] = SafeStripSlashes($vocabularies->getGenreName($prg->get('genre_id')));
		
		$data['id3v2']['TRCK']['encodingid'] = 0;
		$data['id3v2']['TRCK']['data']       = SafeStripSlashes(sotf_Utils::getParameter('title'));
		$data['ape']['track'][] = SafeStripSlashes(sotf_Utils::getParameter('title'));
		
		$data['id3v1']['TCON']['encodingid'] = 0;
		$data['id3v1']['TCON']['data']       = SafeStripSlashes($vocabularies->getGenreName($prg->get('genre_id')));
		$data['ape']['genre'][] = SafeStripSlashes($vocabularies->getGenreName($prg->get('genre_id')));
		$genre = $data['ape']['genre'][0];
		
		$data['id3v1']['TRCK']['encodingid'] = 0;
		$data['id3v1']['TRCK']['data']       = SafeStripSlashes(sotf_Utils::getParameter('title'));
		$data['ape']['track'][] = SafeStripSlashes(sotf_Utils::getParameter('title'));
		
		// COMMENT
		$data['id3v1']['COMM'][0]['encodingid']  = 0;
		$data['id3v1']['COMM'][0]['language']    = 'eng';
		$data['id3v1']['COMM'][0]['description'] = '';
		$data['id3v1']['COMM'][0]['data']        = SafeStripSlashes(sotf_Utils::getParameter('keywords'));
		$data['ape']['comments'][] = SafeStripSlashes(sotf_Utils::getParameter('keywords'));
		$comment = $data['ape']['comments'][0];
		
		
		$data['id3v2']['TALB']['encodingid'] = 0;
		$data['id3v2']['TALB']['data']       = '*ID3Tags modified by SOTF*';
		$data['ape']['album'][] = '*ID3Tags modified by SOTF*';
				

	WriteID3v1($file, $title, $artist, '*ID3Tags modified by SOTF*' , $year, $comment, $genre, $title, false );
	WriteID3v2($file, $data, 3, 0, true, 0, true);

		if (!empty($_POST['VersionToEditAPE'])) {
			echo 'APE changes'.(WriteAPEtag($EditorFilename, $data['ape'], false) ? '' : ' NOT').' written successfully<HR>';
			}
				
					}
			
		}
		
   //-----------------------------------------------------------------------------------------------------

				
  // save
  
  // added by wolfgang csacsinovits & martin schmidt 05-09-27
  if($error_count>0){
	  $smarty->assign("FIELD_ERROR", $field_error); 
	  $smarty->assign("ERROR_COUNT", $error_count);
  }
 
  // -----------------------------------

  elseif ($finishpublish) {
    $prg->publish();
    $page->redirect("editor.php");
  } elseif ($finish) {
    $prg->update();
    $page->redirect("editor.php");
  } else {
    $prg->update();
    $page->redirect("editMeta.php?id=$prg->id");
  }
}

$smarty->assign('PRG_ID', $prgId);
$smarty->assign('PRG_TITLE', $prg->get('title'));


// delete role
$delrole = sotf_Utils::getParameter('delrole');
$roleid = sotf_Utils::getParameter('roleid');
if($delrole) {
  checkPerm($prg, 'change');
  $role = new sotf_NodeObject('sotf_object_roles', $roleid);
  $c = new sotf_Contact($role->get('contact_id'));
  $role->delete();
  $msg = $page->getlocalizedWithParams("deleted_contact", $c->get('name'));
  $page->addStatusMsg($msg, false);
  $page->redirect("editMeta.php?id=$prgId#roles");
  exit;
}

// delete right
$delright = sotf_Utils::getParameter('delright');
$rid = sotf_Utils::getParameter('rid');
if($delright) {
  checkPerm($prg, 'change');
  $right = new sotf_NodeObject('sotf_rights', $rid);
  $right->delete();
  //$msg = $page->getlocalizedWithParams("deleted_", $c->get('name'));
  //$page->addStatusMsg($msg, false);
  $page->redirect("editMeta.php?id=$prgId#rights");
  exit;
}

// manage permissions
$delperm = sotf_Utils::getParameter('delperm');
if($delperm) {
  checkPerm($prg, 'authorize');
  $userid = sotf_Utils::getParameter('userid');
  if(empty($userid) || !is_numeric($userid)) {
    raiseError("Invalid userid: $userid");
  }
  $username = $user->getUsername($userid);
  if(empty($username)) {
    raiseError("Invalid userid: $userid");
  }
  $permissions->delPermission($prg->id, $userid);
  $msg = $page->getlocalizedWithParams("deleted_permissions_for", $username);
  $page->addStatusMsg($msg, false);
  $page->redirect("editMeta.php?id=$prgId#perms");
  exit;
}

// icon and jingle

// upload icon
$uploadIcon = sotf_Utils::getParameter('uploadicon');
if($uploadIcon) {
  checkPerm($prg, 'change');
  $file =  $user->getUserDir() . '/' . $_FILES['userfile']['name'];
  moveUploadedFile('userfile',  $file);
  if ($prg->setIcon($file)) {
    //$page->addStatusMsg("ok_icon");
  } else {
    $page->addStatusMsg("error_icon");
  }  
  $page->redirect("editMeta.php?id=$prgId#icon");
  exit;
}

// select icon from user files
$seticon = sotf_Utils::getParameter('seticon');
$filename = sotf_Utils::getParameter('filename');
if($seticon) {
  checkPerm($prg, 'change');
  $file = $user->getUserDir() . '/' . $filename;
  if ($prg->setIcon($file)) {
    //$page->addStatusMsg("ok_icon");
  } else {
    //$page->addStatusMsg("error_icon");
  }
  $page->redirect("editMeta.php?id=$prgId#icon");
  exit;
}



// generate output

// general data
if($new)


     $smarty->assign("NEW",1);
	 $smarty->assign('PRG_DATA', $prg->getAllForHTML());

//--- modified by martin schmidt 05-09-11
if($prg->get('broadcast_date')) $smarty->assign('BROADCAST_TIME', strtotime($prg->get('broadcast_date')));
//----------------------------------


// station data
$station = $prg->getStation();
$smarty->assign('STATION_DATA', $station->getAllForHTML());

// other stations
$stations = $permissions->listStationsForEditor(false);
//debug("stations", $stations);
if(count($stations) > 1) {
  $smarty->assign("CHANGE_STATION", 1);
}

// series data
$series = $prg->getSeries();
if($series)
     $smarty->assign('SERIES_DATA', $series->getAllForHTML());
$smarty->assign('MY_SERIES', $permissions->mySeriesData($prg->get('station_id')));
     
// roles and contacts
$smarty->assign('ROLES', $prg->getRoles());

// user permissions: editors and managers
$smarty->assign('PERMISSIONS', $permissions->listUsersAndPermissionsLocalized($prg->id));

// topics
$smarty->assign('TOPICS', $prg->getTopics());

// genres
$genres = $vocabularies->getGenres();
array_unshift($genres, array('id'=>0, 'name'=> $page->getlocalized("no_genre")));
$smarty->assign('GENRES_LIST', $genres);

// languages
$prg->getLanguageSelectBoxes();

// rights sections
$smarty->assign('RIGHTS', $prg->getAssociatedObjects('sotf_rights', 'start_time'));

// for icon
$smarty->assign('USERFILES',$user->getUserFiles());

$smarty->assign('ICON', $prg->cacheIcon());

//$smarty->assign('OKURL',$PHP_SELF . '?station=' . rawurlencode($station));

$page->send();

?>