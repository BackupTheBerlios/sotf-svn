<?php
require("init.inc.php");

$hitsPerPage = $sotfVars->get("hitsPerPage", 30);

$seriesid = sotf_Utils::getParameter('seriesid');
$start = sotf_Utils::getParameter('start');

if(!$seriesid)
     raiseError("No series selected!");

$series = & new sotf_Series($seriesid);
$station = $series->getStation();

$page->errorURL = "showSeries.php?seriesid=$seriesid";
$page->setTitle($series->get('title'));

$smarty->assign('SERIES_ID',$seriesid);
$smarty->assign('SERIES_DATA',$series->getAll());
$smarty->assign('STATION_DATA',$station->getAll());
$smarty->assign('ROLES', $series->getRoles());

$numProgs = $series->numProgrammes();
$limit = $page->splitList($numProgs, $_SERVER["REQUEST_URI"]);
$progs = $series->listProgrammes($limit["from"] , $limit["maxresults"]);

if($progs) {
  while(list(,$prog) = each($progs)) {
    $pd = $prog->getAll();
    $progList[] = $pd;
  }
  $smarty->assign('PROGS',$progList);
/*
  if (!$start)
     $start = 0;

  $prev = $start - $hitsPerPage;
  if ($prev < 0) {
    $prev = false;
  }
  $next = $start + $hitsPerPage;
  if ($next >= $numProgs) {
    $next = false;
  }

  $smarty->assign('PROG_SPLIT', array('count' => $numProgs,
                                      'start' => $start + 1,
                                      'max'   => $start + count($progs),
                                      'displayed' => count($progs),
                                      'next' => $next,
                                      'prev' => $prev)
                  );
*/
}

$page->send();

?>
