<?php
require("init.inc.php");

$smarty->assign("PAGETITLE", $page->getlocalized("addtoseries"));

$page->popup = true;
$page->forceLogin();

$prgId = sotf_Utils::getParameter('prgid');

$prg = & new sotf_Programme($prgId);

if(!hasPerm($prgId, 'change')) {
  raiseError("no permission to change metadata in this programme");
  exit;
}

$save = sotf_Utils::getParameter('save');
if($save) {
  $prg->setWithParam("series_id");
  $prg->update();
  $page->redirect("closeAndRefresh.php");
}

$smarty->assign('PRG_ID', $prgId);
$smarty->assign('PRG_TITLE', $prg->get('title'));
$smarty->assign('PRG_SERIES', $prg->get('series_id'));

$smarty->assign('MY_SERIES', $permissions->mySeriesData($prg->get('station_id')));

// generate output

$page->sendPopup();

?>