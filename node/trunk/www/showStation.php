<?php
require("init.inc.php");

$hitsPerPage = $sotfVars->get("hitsPerPage", 30);

$stationid = sotf_Utils::getParameter('stationid');
$start = sotf_Utils::getParameter('start');

if(!$stationid)
     raiseError("No station selected!");

$st = & new sotf_Station($stationid);

$page->errorURL = "showStation.php?stationid=$stationid";
$page->setTitle($st->get('name'));

$smarty->assign('STATION_ID',$stationid);
$smarty->assign('STATION',$st->get('name'));
$smarty->assign('STATION_DATA',$st->getAll());
if($st->isLocal()) {
  $smarty->assign('IS_LOCAL',1);
}
$smarty->assign('ROLES', $st->getRoles());

if ($st->getIcon()) {
  $smarty->assign('ICON','1');
}

if ($entered)
     $smarty->assign('ENTERED',$entered);
if (!$start)
     $start = 0;

$seriesList = $st->listSeries();
while(list(,$series) = each($seriesList)) {
  $sd = $series->getAll();
  $sd['count'] = $series->numProgrammes();
  $seriesData[] = $sd;
}

$smarty->assign('SERIES', $seriesData);

$numProgs = $st->numProgrammes();
$limit = $page->resultspage($numProgs, $_SERVER["REQUEST_URI"]);
$progs = $st->listProgrammes($limit["from"] , $limit["maxresults"]);

if($progs) {

  while(list(,$prog) = each($progs)) {
    $pd = $prog->getAll();
    $progList[] = $pd;
  }
  $smarty->assign('PROGS',$progList);

/*
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
