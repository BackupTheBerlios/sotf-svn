<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");
require("$classdir/sotf_AdvSearch.class.php");


$pattern = sotf_Utils::getSQLSafeParameter('pattern');
$language = sotf_Utils::getSQLSafeParameter('language');
if($pattern) {
//  debug("language", $language);

if ($language == "any_language") $language = false;

$advsearch = new sotf_AdvSearch();						//create new search object object with this array

  $total = $advsearch->simpleSearch($pattern, $language);
  $limit = $page->splitList($total, $_SERVER["REQUEST_URI"]);
  $result = $advsearch->getSimpleSearchResults($limit["from"] , $limit["to"]);

  // cache icons for results
  for($i=0; $i<count($result); $i++) {
    $result[$i]['icon'] = sotf_Blob::cacheIcon($result[$i]['id']);
  }

  $smarty->assign('RESULTS', $result);
  $smarty->assign('PATTERN', $pattern);
  $smarty->assign('LANGUAGE', $language);
}

$searchLangs = $languages;
array_unshift($searchLangs, "any_language");

for($i=0; $i<count($searchLangs); $i++) {
  $langNames[$i] = $page->getlocalized($searchLangs[$i]);
}

$smarty->assign('searchLangs', $searchLangs);
$smarty->assign('langNames', $langNames);

$page->send();

?>
