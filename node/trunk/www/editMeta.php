<?php  // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

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
  $params = array('title'=>'text',
                  'alternative_title'=>'text',
                  'episode_title'=>'text',
                  'episode_sequence'=>'number',
                  'keywords'=>'text',
                  'abstract'=>'text',
                  'genre_id'=>'number',
                  'spatial_coverage'=>'text',
                  'temporal_coverage'=>'date',
                  'production_date'=>'date',
                  'broadcast_date'=>'date',
                  'expiry_date'=>'date'
                  );
  foreach($params as $param=>$type) {
    $value = sotf_Utils::getParameter($param);
    if($type=='text') {
      $value = strip_tags($value);
    } elseif($type=='number') {
      if(empty($value))
        $value = 0;
      elseif(!is_numeric($value)) {
        addError($page->getlocalized('not_a_number') . ": $value");
        continue;
      }
    } elseif($type=='date') {
      if (sotf_Utils::getParameter($param . '_radio1') != "unselected") {
	      $value = sotf_Utils::getParameter($param . 'Year') . '-'
	        . sotf_Utils::getParameter($param . 'Month') . '-'
	        . sotf_Utils::getParameter($param . 'Day');
			if($param == 'broadcast_date') {
			  $value = $value . ' ' . sotf_Utils::getParameter('broadcast_timeHour') . ':' . sotf_Utils::getParameter('broadcast_timeMinute') . ' ' . $db->myTZ();
			}
		} else {
		  $value = NULL;
		}
    }
    $prg->set($param, $value);
  }
  // language hack
  $prg->setLanguageWithParams();
  // save
  if ($finishpublish) {
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
$smarty->assign('BROADCAST_TIME', strtotime($prg->get('broadcast_date')));

// station data
$station = $prg->getStation();
$smarty->assign('STATION_DATA', $station->getAllForHTML());

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