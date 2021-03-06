<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: Andr�s Micsik, M�t� Pataki, Tam�s K�zdi 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

define('ITEMS_IN_RSS', 10);

require("init.inc.php");
require_once($config['classdir'] . "/xmlwriterclass.php");
require_once($config['classdir'] . "/rss_writer_class.php");
require_once($config['classdir'] . "/sotf_AdvSearch.class.php");

$stationName = sotf_Utils::getParameter('station');
$userName = sotf_Utils::getParameter('user');
$queryName = sotf_Utils::getParameter('qname');
$query = sotf_Utils::getParameter('query');

function createImageTag($url, $title, $link) {
  return "<url>$url</url><title>$title</title><link>$link</link>";
}

// calculate day to list things after that
$now = getDate();
$dayInThePast = mktime(0,0,0, $now['mon'], $now['mday']-10, $now['year']);
$fromDay = date('Y-m-d', $dayInThePast);

// prepare RSS writer
$rss_writer_object=new rss_writer_class;
$rss_writer_object->specification="1.0";
$rss_writer_object->about=$config['rootUrl'] . "/rss.php";
// Specify the URL of an optional XSL stylesheet. This lets the document be rendered automatically in XML capable browsers.
$rss_writer_object->stylesheet=$config['rootUrl'] . "/static/rss2html.xsl";
// When generating RSS version 1.0, you may declare additional namespaces that enable the use of 
// more property tags defined by extension modules of the RSS specification.
$rss_writer_object->rssnamespaces["dc"]="http://purl.org/dc/elements/1.1/";

// do the job, fill in RSS
if($stationName) {
  // send list of new progs in station
  $station = sotf_Station::getByName($stationName);
  if(!$station)
	 raiseError("no such station: $stationName");

  // define channel
  $properties=array();
  $properties["description"]="New programmes at $stationName";
  $properties["link"]=$config['rootUrl'] . "/showStation.php/" . $station->id;
  $properties["title"]="$stationName";
  //$properties["language"]="en";
  $properties["dc:date"]= date("Y-m-d H:i:s");// "2002-05-06T00:00:00Z";
  $rss_writer_object->addchannel($properties);

  // get and cache station icon
  $stationData = $station->getAllWithIcon();
  if($stationData['icon']) {
	 // define icon for station
	 $properties=array();
	 $properties["url"]=$config['cacheUrl'] . "/$station->id.png";
	 $properties["link"]=$config['rootUrl'] . "/showStation.php/" . $station->id;
	 $properties["title"]="$stationName logo";
	 //$properties["description"]="";
	 $rss_writer_object->addimage($properties);
  }

  // add items
  $newProgs = $station->listProgrammes(0, ITEMS_IN_RSS);
  debug("progs", $newProgs);
  foreach($newProgs as $prog) {
	 $properties=array();
	 $properties["description"]= $prog->get('abstract');
	 $properties["link"]= $config['rootUrl'] . "/get.php?id=".$prog->id;
	 $properties["title"]= $prog->get('title');
	 $properties["dc:date"]= $prog->get('production_date');
	 $rss_writer_object->additem($properties);
  }

  // define search box
  $properties=array();
  // The name of the text input form field
  $properties["name"]="pattern";
  $properties["link"]=$config['rootUrl'] . "/search.php?language=any_language&station=$stationName";
  $properties["title"]="Search for:";
  $properties["description"]="Search in $stationName";
  $rss_writer_object->addtextinput($properties);

} elseif($userName) {
  // user's saved query

  $userid = sotf_User::getUserid($userName);
  if(!$userid)
	 raiseError("no such user: $userName");
  $user2 = new sotf_User($userid);
  $prefs2 = $user2->getPreferences();
  debug('saved queries', $prefs2->savedQueries);
  $query = $prefs2->getQuery($queryName);
  if(!$query)
	 raiseError("no such user query: $userName/$queryName");

  // Define the properties of the channel.
  $properties=array();
  $properties["description"]="Results of the StreamOnTheFly query $userName/$queryName";
  $properties["link"]=$config['rootUrl'] . "";
  $properties["title"]="StreamOnTheFly query results";
  //$properties["language"]="en";
  $properties["dc:date"]= date("Y-m-d H:i:s");// "2002-05-06T00:00:00Z";
  $rss_writer_object->addchannel($properties);
	
  //  If your channel has a logo, before adding any channel items, specify the logo details this way.
  $properties=array();
  $properties["url"]=$config['rootUrl'] . "/static/sotflogosmall.gif";
  $properties["link"]=$config['rootUrl'] . "";
  $properties["title"]="StreamOnTheFly logo";
  $properties["description"]="World wide network of radio archives";
  $rss_writer_object->addimage($properties);
	
  //  Then add your channel items one by one.
  $advsearch = new sotf_AdvSearch();
  $advsearch->Deserialize($query);
  $query = $advsearch->GetSQLCommand();
  $res = $db->limitQuery($query, 0, ITEMS_IN_RSS);
  $hits = array();
  while (DB_OK === $res->fetchInto($row)) {
	 //$row['icon'] = sotf_Blob::cacheIcon($row['id']);
	 $hits[] = $row;
  }
  foreach($hits as $prog) {
	 $properties=array();
	 $properties["description"]= $prog['abstract'];
	 $properties["link"]= $config['rootUrl'] . "/get.php?id=".$prog['id'];
	 $properties["title"]= $prog['title'];
	 $properties["dc:date"]= $prog['production_date'];
	 $rss_writer_object->additem($properties);
  }

} elseif($query) {
  // send results of advanced query given as string

  // Define the properties of the channel.
  $properties=array();
  $properties["description"]="Results of StreamOnTheFly query";
  $properties["link"]=$config['rootUrl'] . "";
  $properties["title"]="StreamOnTheFly query results";
  //$properties["language"]="en";
  $properties["dc:date"]= date("Y-m-d H:i:s");// "2002-05-06T00:00:00Z";
  $rss_writer_object->addchannel($properties);
	
  //  If your channel has a logo, before adding any channel items, specify the logo details this way.
  $properties=array();
  $properties["url"]=$config['rootUrl'] . "/static/sotflogosmall.gif";
  $properties["link"]=$config['rootUrl'] . "";
  $properties["title"]="StreamOnTheFly logo";
  $properties["description"]="World wide network of radio archives";
  $rss_writer_object->addimage($properties);
	
  //  Then add your channel items one by one.
  $advsearch = new sotf_AdvSearch();
  $advsearch->Deserialize($query);
  $query = $advsearch->GetSQLCommand();
  $res = $db->limitQuery($query, 0, ITEMS_IN_RSS);
  $hits = array();
  while (DB_OK === $res->fetchInto($row)) {
	 //$row['icon'] = sotf_Blob::cacheIcon($row['id']);
	 $hits[] = $row;
  }
  foreach($hits as $prog) {
	 $properties=array();
	 $properties["description"]= $prog['abstract'];
	 $properties["link"]= $config['rootUrl'] . "/get.php?id=".$prog['id'];
	 $properties["title"]= $prog['title'];
	 $properties["dc:date"]= $prog['production_date'];
	 $rss_writer_object->additem($properties);
  }

} else {

  // Define the properties of the channel.
  $properties=array();
  $properties["description"]="New programmes at StreamOnTheFly";
  $properties["link"]=$config['rootUrl'] . "";
  $properties["title"]="StreamOnTheFly";
  //$properties["language"]="en";
  $properties["dc:date"]= date("Y-m-d H:i:s");// "2002-05-06T00:00:00Z";
  $rss_writer_object->addchannel($properties);
	
  //  If your channel has a logo, before adding any channel items, specify the logo details this way.
  $properties=array();
  $properties["url"]=$config['rootUrl'] . "/static/sotflogosmall.gif";
  $properties["link"]=$config['rootUrl'] . "";
  $properties["title"]="StreamOnTheFly logo";
  $properties["description"]="World wide network of radio archives";
  $rss_writer_object->addimage($properties);
	
  //  Then add your channel items one by one.
  $newProgs = sotf_Programme::getNewProgrammes($fromDay, ITEMS_IN_RSS);
  foreach($newProgs as $prog) {
	 $properties=array();
	 $properties["description"]= $prog['abstract'];
	 $properties["link"]= $config['rootUrl'] . "/get.php?id=".$prog['id'];
	 $properties["title"]= $prog['title'];
	 $properties["dc:date"]= $prog['production_date'];
	 $rss_writer_object->additem($properties);
  }

	//  If your channel has a search page, after adding the channel items, specify a search form details this way.
	$properties=array();
	// The name property if the name of the text input form field
	$properties["name"]="pattern";
	$properties["link"]=$config['rootUrl'] . "/search.php?language=any_language";
	$properties["title"]="Search for:";
	$properties["description"]="Search in StreamOnTheFly";
	$rss_writer_object->addtextinput($properties);
	
}

if($rss_writer_object->writerss($output)) {
		
  // If the document was generated successfully, you may now output it.
  Header("Content-Type: text/xml; charset=\"".$rss_writer_object->outputencoding."\"");
  Header("Content-Length: ".strval(strlen($output)));
  echo $output;
} else {

  //  If there was an error, output it as well.
  Header("Content-Type: text/plain");
  echo ("Error: ".$rss_writer_object->error);
}

$page->logRequest();

?>