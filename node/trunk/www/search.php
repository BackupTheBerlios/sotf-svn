<?php
require("init.inc.php");



$pattern = sotf_Utils::getSQLSafeParameter('pattern');
$language = sotf_Utils::getSQLSafeParameter('language');
if($pattern) {
  debug("language", $language);

  $total = sotf_Programme::countSearch($pattern, $language);
  $limit = $page->splitList($total, $_SERVER["REQUEST_URI"]);
  $result = sotf_Programme::simpleSearch($pattern, $language, $limit["from"] , $limit["maxresults"]);
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
