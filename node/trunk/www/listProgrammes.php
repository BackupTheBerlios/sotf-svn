<?php

define (HITS,30);

require("init.inc.php");

$station = sotf_Utils::getParameter('station');
$entered = sotf_Utils::getParameter('entered');
$start = sotf_Utils::getParameter('start');
$series = sotf_Utils::getParameter('series');

if ($station)
	$smarty->assign('STATION',$station);
if ($entered)
	$smarty->assign('ENTERED',$entered);
if (!$start)
	$start = 0;

$st = & new sotf_Station($station);

if ($series)
{
	$s = & new sotf_Series("$station:$series"); // TODO remove ':' hack
	$smarty->assign('SERIES',$series);
	debug("series dump", $s);
	$seriesdata = array(
						id				=> $s->get('id'),
						track			=> $s->get('track'),
						title			=> $s->get('title'),
						author			=> $s->get('author'),
						production_date	=> $s->get('production_date')
						);
	$smarty->assign('SERIESDATA',$seriesdata);
	$numProgs = $s->numProgrammes();
	$progs = $s->listProgrammes($start,HITS);
}
else
{
	$serlist = $st->listSeries();
	$series_list = array();
	for ($i=0;$i<count($serlist);$i++)
		$series_list[] = array(
								id			=> $serlist[$i]->get('id'),
								series_id	=> $serlist[$i]->get('series_id'),
								title		=> $serlist[$i]->get('title'),
							);
	$smarty->assign('SERIES_LIST',$series_list);
	$numProgs = $st->numProgrammes();
	$progs = $st->listProgrammes($start, HITS);
}

$smarty->assign('EDIT_PERMISSION',sotf_Permission::get('write', $station));

if (sotf_Permission::get('station_manager') || sotf_Permission::get('station_manager',$station))
{
	$smarty->assign('STATION_MANAGER',true);
}
if ($progs)
{
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
