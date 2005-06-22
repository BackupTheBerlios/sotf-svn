<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

  /*  
	* $Id$
	* Authors: András Micsik
	*          at MTA SZTAKI DSD, http://dsd.sztaki.hu
	*/

define('ITEMS_IN_RSS', 10);

require("init.inc.php");
require_once($config['classdir'] . "/sotf_AdvSearch.class.php");

//$prgId = sotf_Utils::getParameter('id');
$stationId = sotf_Utils::getParameter('station');
$seriesId = sotf_Utils::getParameter('series');
//$userName = sotf_Utils::getParameter('user');
//$queryName = sotf_Utils::getParameter('qname');
$query = sotf_Utils::getParameter('query');

function writeTag(&$rss, $tag, $value, $lang='', $attr='') {
  if($lang)
	 $langAttr = "xml:lang=\"$lang\"";
  if(is_array($attr)) {
	 foreach($attr as $k=>$v) {
		$attrNew .= $k . '="' . htmlspecialchars($v) . '" ';
	 }
	 $attr = $attrNew;
  }
  if(!$value) {
	 $rss .= "\n<$tag $attr $langAttr />";
  } else {
	 $value = htmlspecialchars($value);
	 $rss .= "\n<$tag $attr $langAttr>$value</$tag>";
  }
}

function createImageTag($url, $title, $link) {
  return "<url>$url</url><title>$title</title><link>$link</link>";
}

function getW3CDate() {
  $retval = date("Y-m-d\TH:i:sO");// 1997-07-16T19:20:30+01:00  "2002-05-06T00:00:00Z";
  $retval = substr($retval, 0, -2) . ':' . substr($retval, -2);
  return $retval;
}

/** this is a hack to convert pgsql date format to W3C date format required by RSS */
function toW3CDate($date) {
  //2003-02-21 00:00:00+01
  //debug("date", $date);
  //$retval = substr($date, 0, 10) . 'T' . substr($date, 12) . ':00';
  //return $retval;
  return $date;
}

function selectAudio(&$prg) {
  global $config;
  $files = $prg->listAudioFiles('TRUE','kbps DESC');
  foreach($files as $f) {
	 if($f['download_access']=='t') {
		//$f['url'] = $config['rootUrl'] . '/getFile.php/' . $f['filename'] . '?audio=1&id=' . $prg->id . '&filename=' . $f['filename'];
		//$f['url'] = $config['rootUrl'] . '/getFile.php?audio=1&id=' . $prg->id . '&filename=' . $f['filename'];
		$baseUrl = sotf_Node::getHomeNodeRootUrl($prg);
		$f['url'] = $baseUrl . '/getFile.php/fid__' . $f['id'].".mp3"; // wreutz: very dirty hack for ipooder to work on os x
		return $f;
	 }
  }
  /*
  if(!$retval) {
	 foreach($files as $f) {
		if($f['stream_access']=='t') {
		  $f['url'] = $config['rootUrl'] . '/listen.php?id=' . $prg->id . '&fileid=' . $f['id'];
		  return $f;
		}
	 }
  }
  */
  return NULL;
}

function addItem(&$rss, &$prog) {
  global $config;
  $rss .= "\n\n<item>";
  writeTag($rss, "title", $prog->get('title'));
  writeTag($rss, "link", $config['rootUrl'] . "/get.php?id=".$prog->id);
  writeTag($rss, "pubDate", toW3CDate($prog->get('entry_date')));
  writeTag($rss, "description", $prog->get('abstract'));
  $audioAttrs = selectAudio($prog);
  if($audioAttrs) {
	 //$filepath = $prog->getFilePath($audioAttrs);
	 //$tmpFile = linkAudio($filepath, $audioAttrs);
	 $enclAttrs = array('type' => 'audio/mpeg',
							  'length' => $audioAttrs['filesize'],
							  //'url' => $config['tmpUrl'] . '/' . basename($tmpFile),
							  'url' => $audioAttrs['url'],
							  //'url' => $config['tmpUrl'] . '/' . 'au_011pr105_budh1204_24kbps_1chn_22050Hz.mp3',
							  );
	 writeTag($rss, "enclosure", NULL, NULL, $enclAttrs);
  }
  $rss .= "\n</item>";
}

function linkAudio($filepath, $audioAttrs) {
  global $config;
  $tmpFileName = 'au_' . $audioAttrs['id'] . '_' . basename($filepath);
 
  $tmpFile = $config['tmpDir'] . "/$tmpFileName";
  $file = @readlink($tmpFile);
  if($file) {
	 if(!is_readable($file)) {
		logError("Bad symlink: $tmpFile to $file");
		unlink($tmpFile);
		$file = false;
	 }
  }
  if(!$file) {
	 if(!symlink($filepath, $tmpFile)) {
		raiseError("symlink failed in tmp dir");
	 }
  }
  return $tmpFile;
}

// calculate day to list things after that
$now = getDate();
$dayInThePast = mktime(0,0,0, $now['mon'], $now['mday']-10, $now['year']);
$fromDay = date('Y-m-d', $dayInThePast);

// prepare RSS writer
$rss = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
$rss .= '<rss version="2.0">';

// do the job, fill in RSS
if($seriesId) { //***************** SERIES *************************************
	 
	 //list of new progs in series
	 $series = $repository->getObject($seriesId);
    if(!$series)
		raiseError("no such series: $seriesId");
	 
	 // define channel
	 $rss .= "\n<channel>";
	 writeTag($rss, "title", $series->get('name'));
	 writeTag($rss, "link", $config['rootUrl'] . "/showSeries.php/" . $series->id);
	 writeTag($rss, "language", $series->get2LetterLanguageCode());
	 writeTag($rss, "description",$series->get('description'));
	 // TODO: editor e-mail
	 // TODO copyright
	 writeTag($rss, "webMaster", $config['adminEmail']);
	 //writeTag($rss, "pubDate", getW3CDate());

	 // get and cache series icon
	 $seriesData = $series->getAllWithIcon();
	 if($seriesData['icon']) {
		// define icon for series
		$rss .= "\n<image>";
		writeTag($rss, "url", $config['cacheUrl'] . "/$series->id.png");
		writeTag($rss, "link", $config['rootUrl'] . "/showSeries.php/" . $series->id);
		writeTag($rss, "title", $series->get('name'));
		// width, height
		$rss .= "\n</image>";
	 }

	 // add items
	 $newProgs = $series->listProgrammes(0, ITEMS_IN_RSS);
	 //debug("progs", $newProgs);
	 foreach($newProgs as $prog) {
		addItem($rss, $prog);
	 }
	 $rss .= "\n</channel>";

} elseif($stationId) { // *************** STATION *******************

  // send list of new progs in station
  
  if($repository->looksLikeId($stationId)) {
	 $station = $repository->getObject($stationId);
  }
  if(!$station)
	 $station = sotf_Station::getByName($stationId);
  if(!$station)
	 raiseError("no such station: $stationName");

  // define channel
  $rss .= "\n<channel>";
  writeTag($rss, "title", $station->get('name'));
  writeTag($rss, "link", $config['rootUrl'] . "/showStation.php/" . $station->id);
  writeTag($rss, "language", $station->get2LetterLanguageCode());
  writeTag($rss, "description", $station->get('description'));
  // TODO: editor e-mail
  writeTag($rss, "webMaster", $config['adminEmail']);
  //writeTag($rss, "pubDate", getW3CDate());

  // get and cache station icon
  $stationData = $station->getAllWithIcon();
  if($stationData['icon']) {
	 // define icon for station
	 $rss .= "\n<image>";
	 writeTag($rss, "url", $config['cacheUrl'] . "/$station->id.png");
	 writeTag($rss, "link", $config['rootUrl'] . "/showStation.php/" . $station->id);
	 writeTag($rss, "title", $station->get('name'));
	 // width, height
	 $rss .= "\n</image>";
  }

  // add items
  $newProgs = $station->listProgrammes(0, ITEMS_IN_RSS);
  //debug("progs", $newProgs);
  foreach($newProgs as $prog) {
	 addItem($rss, $prog);
  }
  
  /*
	// define search box
		  $properties=array();
		  // The name of the text input form field
		  $properties["name"]="pattern";
		  $properties["link"]=$config['rootUrl'] . "/search.php?language=any_language&station=$stationName";
		  $properties["title"]="Search for:";
		  $properties["description"]= $page->getlocalizedWithParams('search_in_station', $stationName);
		  $rss_writer_object->addtextinput($properties);
  */
  $rss .= "\n</channel>";

} elseif($query) { // ***************** SERIALIZED QUERY ****************

  $advsearch = new sotf_AdvSearch();
  $advsearch->Deserialize($query);

  // send results of advanced query given as string
  $rss .= "\n<channel>";

  $queryTags = $advsearch->GetHumanReadable();
  for($i=0; $i<count($queryTags); $i++) {  // TODO: this is a rough solution
	 if($i == count($queryTags) -1)
		$queryTexts[] = $queryTags[$i][1] . ' ' . $queryTags[$i][2] . ' ' . $queryTags[$i][3];
	 else
		$queryTexts[] = $queryTags[$i][1] . ' ' . $queryTags[$i][2] . ' ' . $queryTags[$i][3] . ' ' . $queryTags[$i][0];
  }
  $queryText = implode(' ', $queryTexts);
  writeTag($rss, "title", "StreamOnTheFly query results");
  writeTag($rss, "description", $queryText);
  writeTag($rss, "link", $config['rootUrl'] . "advsearchresults.php?back=true&SQLquerySerial=$query");
  //$properties["language"]="en";
	
  $rss .= "\n<image>";
  writeTag($rss, "url", $config['rootUrl'] . "/static/sotflogosmall.gif");
  writeTag($rss, "link", $config['rootUrl']);
  writeTag($rss, "title", "Results of StreamOnTheFly query");
  // width, height
  $rss .= "\n</image>";

  $query = $advsearch->GetSQLCommand();
  $res = $db->limitQuery($query, 0, ITEMS_IN_RSS);
  $hits = array();
  while (DB_OK === $res->fetchInto($row)) {
	 //$row['icon'] = sotf_Blob::cacheIcon($row['id']);
	 $hits[] = $row;
  }
  foreach($hits as $prog) {
	 $prgObj = &$repository->getObject($prog['id']);
	 addItem($rss, $prgObj);
  }
  $rss .= "\n</channel>";

} else {
        
  raiseError("Need a series or station id!");
	
}

$rss .= "\n</rss>";

// If the document was generated successfully, you may now output it.
Header("Content-Type: text/xml; charset=\"utf-8\"");
Header("Content-Length: ".strval(strlen($rss)));
echo $rss;

$page->logRequest();

?>