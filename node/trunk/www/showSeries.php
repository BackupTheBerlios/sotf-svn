<?php
require("init.inc.php");

$seriesid = sotf_Utils::getParameter('seriesid');

if(!$seriesid)
     raiseError("No series selected!");

// delete prog
$delprog = sotf_Utils::getParameter('delprog');
$prgid = sotf_Utils::getParameter('prgid');
if($delprog) {
  $prg = new sotf_Programme($prgid);
  $prg->delete();
  $page->redirect("showSeries.php?seriesid=$seriesid#progs");
  exit;
}

$series = & new sotf_Series($seriesid);
$series->cacheIcon();
$station = $series->getStation();
$station->cacheIcon();

$page->errorURL = "showSeries.php?seriesid=$seriesid";
$page->setTitle($series->get('title'));

$smarty->assign('SERIES_ID',$seriesid);
$smarty->assign('SERIES_DATA',$series->getAll());
$smarty->assign('STATION_DATA',$station->getAll());
$smarty->assign('ROLES', $series->getRoles());

$numProgs = $series->numProgrammes();
$limit = $page->splitList($numProgs, $_SERVER["REQUEST_URI"], "progs");
$progs = $series->listProgrammes($limit["from"] , $limit["maxresults"]);

if($progs) {
  while(list(,$prog) = each($progs)) {
    $prog->cacheIcon();
    $pd = $prog->getAll();
    $progList[] = $pd;
  }
  $smarty->assign('PROGS',$progList);
}

$page->send();

?>
