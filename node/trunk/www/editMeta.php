<?php
require("init.inc.php");

$smarty->assign("PAGETITLE", $page->getlocalized("editmeta"));
//////$smarty->assign("OKURL", $_SERVER['PHP_SELF'] . "?id=" . rawurlencode($id));

$page->forceLogin();

$prgId = sotf_Utils::getParameter('id');

$finishpublish = sotf_Utils::getParameter('finishpublish');
$finishnotpublish = sotf_Utils::getParameter('finishnotpublish');

$okURL = sotf_Utils::getParameter('okURL');

$prg = & new sotf_Programme($prgId);

if(!$prg->isLocal()) {
  raiseError("You can only edit programmes locally!");
}
if(!hasPerm($prgId, 'change')) {
  raiseError("no permission to change files in this programme");
  exit;
}

$save = sotf_Utils::getParameter('save');
if($save) {
  $params = array('title'=>'text',
                  'alternative_title'=>'text',
                  'episode_title'=>'text',
                  'episode_sequence'=>'number',
                  'keywords'=>'text',
                  'abstract'=>'text',
                  'language'=>'text',
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
      if(!is_numeric($value)) {
        addError($page->getlocalized('not_a_number') . ": $value");
        continue;
      }
    } elseif($type=='date') {
      $value = sotf_Utils::getParameter($param . 'Year') . '-'
        . sotf_Utils::getParameter($param . 'Month') . '-'
        . sotf_Utils::getParameter($param . 'Day');
    }
    $prg->set($param, $value);
  }
  $prg->update();
  $page->redirect("editMeta.php?id=$prg->id");
  /*
  if ($finishpublish) {
    $prg->publish();
    if ($okURL)
      $page->redirect($okURL);
    else
      $page->redirect("editor.php");
  }
  if ($finishnotpublish) {
    $prg->withDraw();
    if ($okURL)
      $page->redirect($okURL);
    else
      $page->redirect("editor.php");
  }
  */
}

$smarty->assign('PRG_ID', $prgId);
$smarty->assign('PRG_TITLE', $prg->get('title'));


// upload to my files
$uploadIcon = sotf_Utils::getParameter('uploadicon');
if($uploadIcon) {
  $file = $user->getUserDir() . '/' . $_FILES['userfile']['name'];
  move_uploaded_file($_FILES['userfile']['tmp_name'], $file);
  if ($prg->setIcon($file)) {
    //$page->addStatusMsg("ok_icon");
  } else {
    $page->addStatusMsg("error_icon");
  }  
  $page->redirect("editMeta.php?id=$prgId#icon");
  exit;
}

// delete role
$delrole = sotf_Utils::getParameter('delrole');
$roleid = sotf_Utils::getParameter('roleid');
if($delrole) {
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
  $right = new sotf_NodeObject('sotf_rights', $rid);
  $right->delete();
  //$msg = $page->getlocalizedWithParams("deleted_", $c->get('name'));
  //$page->addStatusMsg($msg, false);
  $page->redirect("editMeta.php?id=$prgId#rights");
  exit;
}

// manage permissions
$delperm = sotf_Utils::getParameter('delperm');
$username = sotf_Utils::getParameter('username');
if($delperm) {
  $userid = $user->getUserid($username);
  if(empty($userid) || !is_numeric($userid)) {
    raiseError("Invalid username: $username");
  }
  $permissions->delPermission($prg->id, $userid);
  $msg = $page->getlocalizedWithParams("deleted_permissions_for", $username);
  $page->addStatusMsg($msg, false);
  $page->redirect("editMeta.php?id=$prgId#perms");
  exit;
}

// icon and jingle
$seticon = sotf_Utils::getParameter('seticon');
$filename = sotf_Utils::getParameter('filename');
if($seticon) {
  $file = $user->getUserDir() . '/' . $filename;
  if ($prg->setIcon($file)) {
    //$page->addStatusMsg("ok_icon");
  } else {
    //$page->addStatusMsg("error_icon");
  }
  $page->redirect("editMeta.php?id=$prgId#icon");
}

// generate output

// general data
$smarty->assign('PRG_DATA', $prg->getAll());

// roles and contacts
$smarty->assign('ROLES', $prg->getRoles());

// user permissions: editors and managers
$smarty->assign('PERMISSIONS', $permissions->listUsersAndPermissionsLocalized($prg->id));

// genres
$smarty->assign('GENRES_LIST', $repository->getGenres());

// languages
for($i=0; $i<count($languages); $i++) {
  $langNames[$i] = $page->getlocalized($languages[$i]);
}
$smarty->assign('LANG_CODES', $languages);
$smarty->assign('LANG_NAMES', $langNames);

// rights sections
$smarty->assign('RIGHTS', $prg->getAssociatedObjects('sotf_rights', 'start_time'));

// for icon
$smarty->assign('USERFILES',$user->getUserFiles());

if ($prg->getIcon()) {
  $smarty->assign('ICON','1');
}

//$smarty->assign('OKURL',$PHP_SELF . '?station=' . rawurlencode($station));

$page->send();

?>