<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

$seriesid = sotf_Utils::getParameter('id');

if(!$seriesid)
     raiseError("No series selected!");

// delete prog
$delprog = sotf_Utils::getParameter('delprog');
$prgid = sotf_Utils::getParameter('prgid');
if($delprog) {
  $prg = & $repository->getObject($prgid);
  $prg->delete();
  $page->redirect("showSeries.php/$seriesid#progs");
  exit;
}

$series = & $repository->getObject($seriesid);
$station = $series->getStation();

$page->errorURL = "showSeries.php/$seriesid";
$page->setTitle($series->get('title'));

$smarty->assign('SERIES_ID',$seriesid);
$smarty->assign('SERIES_DATA',$series->getAllWithIcon());
$smarty->assign('STATION_DATA',$station->getAllWithIcon());
$smarty->assign('ROLES', $series->getRoles());

$numProgs = $series->numProgrammes();
$limit = $page->splitList($numProgs, "$scriptUrl/$seriesid", "progs");
$progs = $series->listProgrammes($limit["from"] , $limit["maxresults"]);

if($progs) {
  while(list(,$prog) = each($progs)) {
    $pd = $prog->getAllWithIcon();
    $progList[] = $pd;
  }
  $smarty->assign('PROGS',$progList);
}

$page->send();

?>
