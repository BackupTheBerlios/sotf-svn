<?php
require("init.inc.php");

$hitsPerPage = $sotfVars->get("hitsPerPage", 30);

$stationid = sotf_Utils::getParameter('stationid');
$entered = sotf_Utils::getParameter('entered');
$start = sotf_Utils::getParameter('start');
$series = sotf_Utils::getParameter('series');

if(!$stationid)
     raiseError("No station selected!");

$st = & new sotf_Station($stationid);

$smarty->assign('PAGETITLE', $st->get('name'));
$smarty->assign('STATION_ID',$stationid);
$smarty->assign('STATION',$st->get('name'));
$smarty->assign('STATION_DATA',$st->getAll());
if($st->isLocal()) {
  $smarty->assign('IS_LOCAL',1);
}
$smarty->assign('ROLES', $st->getRoles());

if ($st->getLogo()) {
  $smarty->assign('LOGO','1');
}

if ($entered)
     $smarty->assign('ENTERED',$entered);
if (!$start)
     $start = 0;

     /*
	$serlist = $st->listSeries();
	$series_list = array();
	for ($i=0;$i<count($serlist);$i++)
		$series_list[] = array(
								id			=> $serlist[$i]->get('id'),
								series_id	=> $serlist[$i]->get('series_id'),
								title		=> $serlist[$i]->get('title'),
							);
	$smarty->assign('SERIES_LIST',$series_list);
     */

$numProgs = $st->numProgrammes();
$progs = $st->listProgrammes($start, $hitsPerPage);
     

if ($progs) {
	for ($i=0;$i<count($progs);$i++)
    {
      $ITEM_LIST[] = array(
                           id				=> $progs[$i]->get('id'),
                           track			=> $progs[$i]->get('track'),
                           title			=> $progs[$i]->get('title'),
                           author			=> $progs[$i]->get('author'),
                           production_date	=> $progs[$i]->get('production_date')
                           );
    }
}
$prev = $start - HITS;
if ($prev < 0)
{
	$prev = 0;
}
$next = $start + HITS;
if ($next >= $numids)
{
	$next = false;
}
$smarty->assign('NUMIDS',$numids);
$smarty->assign('START',$start);
$smarty->assign('NEXT',$next);
$smarty->assign('PREV',$prev);
$smarty->assign('ITEM_LIST',$ITEM_LIST);

$page->send();

?>
