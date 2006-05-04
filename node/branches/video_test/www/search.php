<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");
require($config['classdir'] . "/sotf_AdvSearch.class.php");


$pattern = sotf_Utils::getSQLSafeParameter('pattern');
$language = sotf_Utils::getSQLSafeParameter('language');
$station = sotf_Utils::getSQLSafeParameter('station');

//ADDED BY Martin Schmidt
$audio = sotf_Utils::getParameter('audio');
$video = sotf_Utils::getParameter('video');

if($pattern) {
  //  debug("language", $language);
  //  if(strpos($pattern, '?') || strpos($pattern, '*') || strpos($pattern, '+')) {
  //	 $invalidPattern = 1;
  //}
  $pattern = str_replace(array('?','*','+'), array(), $pattern);

  if ($language == "any_language") $language = false;

  $advsearch = new sotf_AdvSearch();						//create new search object object with this array

  //$total = $advsearch->simpleSearch($pattern, $language, $station);
  $total = $advsearch->simpleSearch($pattern, $language, $station, $audio, $video); //MOD by Martin Schmidt
  
  $limit = $page->splitList($total, "?pattern=" . urlencode($pattern) . "&language=$language");
  $result = $advsearch->getSimpleSearchResults($limit["from"] , $limit["to"]);

  // cache icons for results
  for($i=0; $i<count($result); $i++) {
    $result[$i]['icon'] = sotf_Blob::cacheIcon2($result[$i]);
  }

  $smarty->assign('RESULTS', $result);
  $smarty->assign('PATTERN', $pattern);
  $smarty->assign('LANGUAGE', $language);
  
  //ADDED BY Martin Schmidt
  $smarty->assign('VIDEO', $video);
  $smarty->assign('AUDIO', $audio);
}
else $page->redirect("index.php"); //ADDED BY Martin Schmidt

$searchLangs = $config['languages'];
array_unshift($searchLangs, "any_language");

for($i=0; $i<count($searchLangs); $i++) {
  $langNames[$i] = $page->getlocalized($searchLangs[$i]);
}

$smarty->assign('searchLangs', $searchLangs);
$smarty->assign('langNames', $langNames);

$page->send();

?>
