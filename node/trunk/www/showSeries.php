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
  $page->redirect(mygetenv('PHP_SELF') . "#progs");
  exit;
}

$series = & $repository->getObject($seriesid);
if(!$series)
	  raiseError("no_such_object");

$station = $series->getStation();

$page->errorURL = $scriptUrl . '/' . $seriesid;
$page->setTitle($series->get('name'));

$smarty->assign('SERIES_ID',$seriesid);
$smarty->assign('SERIES_DATA',$series->getAllWithIcon());
$smarty->assign('STATION_DATA',$station->getAllWithIcon());
$smarty->assign('ROLES', $series->getRoles());
if($series->getJingle())
	  $smarty->assign('JINGLE', 1);

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
