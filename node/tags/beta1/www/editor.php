<?php  // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

$smarty->assign("PAGETITLE", $page->getlocalized("EditorPage"));
$page->forceLogin();
$smarty->assign("OKURL", $_SERVER['PHP_SELF']);


if (!$permissions->isEditor()) {
	raiseError("You have no permission to upload to any station");
	exit;
}

// delete prog
if(sotf_Utils::getParameter('delprog')) {
  $prgid = sotf_Utils::getParameter('prgid');
  $prg = new sotf_Programme($prgid);
  $prg->delete();
  $page->redirect("editor.php");
  exit;
}

if(sotf_Utils::getParameter('addprog')) {
  $fname = sotf_Utils::getParameter('fname');
  $station = sotf_Utils::getParameter('station');
  checkPerm($station, 'add_prog');
  $newPrg = new sotf_Programme();
  $track = preg_replace('/\.[^.]*$/','', $fname);
  debug("create with track", $track);
  $newPrg->create($station, $track);
  $newPrg->setAudio($user->getUserDir() . '/' . $fname);
  $permissions->addPermission($newPrg->id, $user->id, 'admin');
  //$page->redirect("editFiles.php");
  $page->redirect("editFiles.php?new=1&id=" . $newPrg->getID());
  exit;
}

$stationId = sotf_Utils::getParameter('stationid');
if($stationId)
	  $smarty->assign('SELECTED_STATION', $stationId);

$userFtpUrl = str_replace('ftp://', "ftp://$user->name@", $config['userFTP']);
	$smarty->assign("USERFTPURL", $userFtpUrl); 

$stations = $permissions->listStationsForEditor();
if(!empty($stations)) {
     $smarty->assign_by_ref("STATIONS",$stations);
}

$userAudioFiles = new sotf_FileList();
$userAudioFiles->getAudioFromDir($user->getUserDir());
$list = $userAudioFiles->getFileNames();
if(!empty($list)) {
		 $smarty->assign_by_ref("USER_AUDIO_FILES", $list);
}

////form sent
$series = sotf_Utils::getParameter("series");
$filter = sotf_Utils::getParameter("filter");
$sort1 = sotf_Utils::getParameter("sort1");
$sort2 = sotf_Utils::getParameter("sort2");

////from user prefs if first time on page
$prefs = $user->getPreferences();
if ($sort1 == NULL) $sort1 = $prefs->editorSettings[sort1];
if ($sort2 == NULL) $sort2 = $prefs->editorSettings[sort2];
if ($series == NULL) $series = $prefs->editorSettings[series];
if ($filter == NULL) $filter = $prefs->editorSettings[filter];

////default settings if first time here
if ($sort1 == NULL) $sort1 = "entry_date";
if ($sort2 == NULL) $sort2 = "title";
if ($series == NULL) $series = "allseries";
if ($filter == NULL) $filter = "all";

//$max = $db->getAll("SELECT count(*) FROM (".$query.") as count");	//get the number of results
//$max = $max[0]["count"];
$max = $myProgs = sotf_Programme::myProgrammes($series, $filter, $sort1.", ".$sort2, true);	//counts it
$limit = $page->splitList($max, "?series=$series&filter=$filter&sort1=$sort1&sort2=$sort2");
//$result = $db->getAll($query.$limit["limit"]);

$myProgs = sotf_Programme::myProgrammes($series, $filter, $sort1.", ".$sort2.$limit["limit"]);
//$plist = new sotf_PrgList($myProgs);
//// todo sort/filter using sotf_PrgList
//$l = $plist->getList();


$mySeriesData = array();
$mySeriesData[allseries] = $page->getlocalized("allseries");
foreach(sotf_Permission::mySeriesData($stationId) as $s)
	$mySeriesData[$s["id"]] = $s["title"];

$sortby[title] = $page->getlocalized("title");
$sortby[series] = $page->getlocalized("series");
$sortby[station] = $page->getlocalized("station");
$sortby[entry_date] = $page->getlocalized("entry_date");
$sortby[expiry_date] = $page->getlocalized("expiry_date");
$sortby[published] = $page->getlocalized("published");

$filters[all] = $page->getlocalized("all");
$filters[published] = $page->getlocalized("published");
$filters[unpublished] = $page->getlocalized("unpublished");
$filters[urgent] = $page->getlocalized("urgent");
$filters[todo] = $page->getlocalized("todo");
$filters[cat1] = $page->getlocalized("cat1");
$filters[cat2] = $page->getlocalized("cat2");
$filters[cat3] = $page->getlocalized("cat3");
$filters[cat4] = $page->getlocalized("cat4");
$filters[cat5] = $page->getlocalized("cat5");

$flags[none] = $page->getlocalized("none");
$flags[urgent] = $page->getlocalized("urgent");
$flags[todo] = $page->getlocalized("todo");
$flags[cat1] = $page->getlocalized("cat1");
$flags[cat2] = $page->getlocalized("cat2");
$flags[cat3] = $page->getlocalized("cat3");
$flags[cat4] = $page->getlocalized("cat4");
$flags[cat5] = $page->getlocalized("cat5");

$smarty->assign_by_ref("mySeriesData", $mySeriesData);		//all series
$smarty->assign_by_ref("series", $series);			//current serie setting
$smarty->assign_by_ref("sortby", $sortby);			//all sort options
$smarty->assign_by_ref("sort1", $sort1);			//sort by 1
$smarty->assign_by_ref("sort2", $sort2);			//sort by 2
$smarty->assign_by_ref("filters", $filters);			//all filters
$smarty->assign_by_ref("filter", $filter);			//current filter
$smarty->assign_by_ref("flags", $flags);			//all flags
$smarty->assign_by_ref("MYPROGS", $myProgs);			//current programmes to display


////save query settings to the user prefs
$editorSettings[sort1] = $sort1;
$editorSettings[sort2] = $sort2;
$editorSettings[series] = $series;
$editorSettings[filter] = $filter;
$prefs->editorSettings = $editorSettings;
$prefs->save();

$page->send();

?>
