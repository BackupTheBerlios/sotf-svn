<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

  /*  
	* $Id: rss_bridge.php 378 2005-05-20 13:46:10Z wreutz $
	*      based on podcast.php,v 1.2 2005/02/01 14:17:41 micsik Exp $
	* Authors: András Micsik
	*          at MTA SZTAKI DSD, http://dsd.sztaki.hu
	*          Wolfgang Reutz
	*          at University of Applied Sciences Vorarlberg, http://www.fhv.at
	*/

define('ITEMS_IN_RSS', 10);

require("init.inc.php");
require_once($config['classdir'] . "/sotf_AdvSearch.class.php");

$prgId = sotf_Utils::getParameter('id');
$stationId = sotf_Utils::getParameter('station');
$seriesId = sotf_Utils::getParameter('series');
//$userName = sotf_Utils::getParameter('user');
//$queryName = sotf_Utils::getParameter('qname');
$query = sotf_Utils::getParameter('query');
$from = sotf_Utils::getParameter('from');
$count = sotf_Utils::getParameter('count');
if(!$count)
  $count = ITEMS_IN_RSS;

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
		$f['url'] = $baseUrl . '/getFile.php/fid__' . $f['id'];
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
  writeTag($rss, "guid", $prog->get('id'));
  writeTag($rss, "sotf:episodesequence", $prog->get('episode_sequence'));
  writeTag($rss, "sotf:episodetitle", $prog->get('episode_title'));
  writeTag($rss, "sotf:broadcastdate", $prog->get('broadcast_date'));
 
  $files = $prog->getAssociatedObjects('sotf_media_files', 'main_content DESC, filename');
  for($i=0; $i<count($files); $i++) {
    $files[$i] =  array_merge($files[$i], sotf_AudioFile::decodeFormatFilename($files[$i]['format']));
    $files[$i]['playtime_string'] = strftime('%M:%S', $fFiles[$i]['play_length']);
  }
  if ($files) {
    $rss .= "\n<sotf:mediafiles>";
    foreach($files as $f) {
        if ($f['stream_access']='t') {
            #$rss .= "\n<sotf:mediafileitem>";
            #writeTag($rss, "sotf:mediafileurl", $config['rootUrl']."/listen.php/audio.m3u?id=".$prog->get('id'));
            #writeTag($rss, "sotf:mediafileimage", $config['rootUrl']."/static/listen.gif");
            #writeTag($rss, "sotf:mediafiletype", "audio");
            #writeTag($rss, "sotf:mediafilebitrate", $f['bitrate']);
            #writeTag($rss, "sotf:mediafileformat", $f['format']);
            #$rss .= "\n</sotf:mediafileitem>";
            $rss .= "\n<sotf:mediafileitem url=\"".$config['rootUrl']."/listen.php/audio.m3u?id=".$prog->get('id')."\" image=\"".$config['rootUrl']."/static/listen.gif\" type=\"audiostream\" bitrate=\"".$f['bitrate']."\" format=\"".$f['format']."\" />";
        }
        if ($f['download_access']='t') {
            #$rss .= "\n<sotf:mediafileitem>";
            #writeTag($rss, "sotf:mediafileurl", $config['rootUrl']."/getFile.php/".$f['filename']."?audio=1&id=".$prog->get('id')."&filename=".$f['filename']);
            #writeTag($rss, "sotf:mediafileimage", $config['rootUrl']."/static/download.gif");
            #writeTag($rss, "sotf:mediafiletype", "audio");
            #writeTag($rss, "sotf:mediafilebitrate", $f['bitrate']);
            #writeTag($rss, "sotf:mediafileformat", $f['format']);
            #$rss .= "\n</sotf:mediafileitem>";
            $rss .= "\n<sotf:mediafileitem url=\"".$config['rootUrl']."/getFile.php/fid__".$f['id']."\" image=\"".$config['rootUrl']."/static/download.gif\" type=\"audiofile\" bitrate=\"".$f['bitrate']."\" format=\"".$f['format']."\" />";
        }
    }
    $rss .= "\n</sotf:mediafiles>";
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
$rss .= "<rss version=\"2.0\" xmlns:sotf=\"http://sotf.sourceforge.net/rss/2.0/modules/sotf\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\">";

// do the job, fill in RSS
if($prgId) { //************************* PROGRAMME ********************************
    $prg = & $repository->getObject($prgId);
    if(!$prg)
        raiseError("no such object");
    if(!$prg->getBool('published')) {
        raiseError("not_published_yet");
        exit;
    }
    
    // define channel
    $rss .= "\n<channel>";
    writeTag($rss, "link", $config['rootUrl'] . "/get.php/" . $prgId);
    writeTag($rss, "title", ($prg->get('title') ? $prg->get('title') : 'Untitled'));
    writeTag($rss, "description", $prg->get('abstract'));
    writeTag($rss, "language", $prg->get2LetterLanguageCode());
    writeTag($rss, "pubDate", getW3CDate());

    // get and cache programme icon
    $prgData = $prg->getAllWithIcon();
    if($prgData['icon']) {
		// define icon for series
		$rss .= "\n<image>";
		writeTag($rss, "url", $config['cacheUrl'] . "/$prgId.png");
		writeTag($rss, "link", $config['rootUrl'] . "/get.php/" . $prgId);
		writeTag($rss, "title", "programme icon");
		$rss .= "\n</image>";
	 }
    $rss .= "\n</channel>";
}
elseif($seriesId) { //***************** SERIES *************************************
	 
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
	 $newProgs = $series->listProgrammes($from, $count);
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
  $newProgs = $station->listProgrammes($from, $count);
  //debug("progs", $newProgs);
  foreach($newProgs as $prog) {
	 addItem($rss, $prog);
  }
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