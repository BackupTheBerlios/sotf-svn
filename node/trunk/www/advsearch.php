<?php  // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");
require("$classdir/sotf_AdvSearch.class.php");
require("$classdir/sotf_ParamCache.class.php");				//paramcache

$paramcache = & new sotf_ParamCache();					//paramcache
$paramcache->setMaxCache(10);						//paramcache
//sotf_Utils::getParameter						//paramcache
//$paramcache->getRegistered						//paramcache

if ($paramcache->getProcessed())					//paramcache, if already adde, deleted, do not do it again
{
	$paramcache->setParameter("save", false);
	$paramcache->setParameter("deleteq", false);
}

//1. Add terms to the query
$SQLlink = $paramcache->getRegistered('SQLlink');			//linker (OR or AND)
$SQLfield = $paramcache->getRegistered('SQLfield');		//name ot the term to add
$add = $paramcache->getRegistered('add');				//add term button
$new = $paramcache->getRegistered('new');				//new query button

//2. Run query
$sort1 = $paramcache->getRegistered('sort1');			//sort order 1
$sort2 = $paramcache->getRegistered('sort2');			//sort order 2
$dir1 = $paramcache->getRegistered('dir1');			//sort order direction 1
$dir2 = $paramcache->getRegistered('dir2');			//sort order direction 2
$run = $paramcache->getRegistered('run');				//run query button
$run_image = $paramcache->getRegistered('image_x');		//TRANSPARENT_run query button (default by enter)
$portal_http = $paramcache->getRegistered('upload_http');	//if upload pressed, the portal field

//3. Manage your queries
$loadfrom = $paramcache->getRegistered('loadfrom');		//dropdown box with the saved queries
$load = $paramcache->getRegistered('load');			//load button
$default = $paramcache->getRegistered('default');			//make my default button
$deleteq = $paramcache->getRegistered('deleteq');			//delete query button
$save = $paramcache->getRegistered('save');			//save button
$saveas = $paramcache->getRegistered('saveas');			//text field

//Current query
$SQLeq = $paramcache->getRegistered('SQLeq');			//= < > ... values array
$SQLstring = $paramcache->getRegistered('SQLstring');		//last parameter value array

$SQLquerySerial = $paramcache->getRegistered('SQLquerySerial');		//the serialized query come from ADVSEARCH.PHP (hidden field)
if ($SQLquerySerial == "") $SQLquerySerial = $_SESSION["SQLquerySerial"];	//  from ADVSEARCHRESULTS.PHP or somewhere else (session)

if ($SQLquerySerial == "")			//make a new query if first time here
{
	$SQLquery = $_SESSION["SQLquery"];		//get array from session
	$advsearch = new sotf_AdvSearch($SQLquery);	//create search object object with this array
}
else 						//else careate query from loaded
{
	$advsearch = new sotf_AdvSearch();		//create new search object object
	$SQLquery = $advsearch->Deserialize($SQLquerySerial);	//deserialize the content of the hidden field
}

if ($SQLquery == NULL) $advsearch->SetSortOrder();	//set DEFAULT sort order for new queries

if (isset($sort1) AND isset($sort2))
	{
		$advsearch->SetSortOrder($sort1, $sort2);	//set sort order
		$advsearch->setDir($dir1, $dir2);		//set sort order direction
	}


$max = count($SQLeq);
$k = 0;
for ($i=0; $i < $max; $i++)			//go through all the values on the form
{
	$SQLquery[$i][2] = $SQLeq[$i];
	if ($SQLquery[$i][4] == "date")
	{
		$SQLquery[$i][3] = mktime(0,0,0,$SQLstring[$k],$SQLstring[$k+1],$SQLstring[$k+2]);	//	mktime(0,0,0,$month,$day,$year);
		$k += 2;
	}
	elseif ($SQLquery[$i][4] == "length")
	{
		$SQLquery[$i][3] = abs($SQLstring[$k]);
	}
	else	$SQLquery[$i][3] = $SQLstring[$k];
	$k++;
}
$advsearch->sotf_AdvSearch($SQLquery);			//set the inner variables of the class as well

if($add)									////add term button pressed
{
	//nagyon kell figyelni a speci karektereket, nehogy SQL parancsot tegyen bele
	$SQLquery = $advsearch->AddRow($SQLlink, $SQLfield);
//	var_dump($SQLlink."!".$SQLfield);
}
elseif (($run or ($run_image=="0")) and $SQLquery!=NULL)			////run query button pressed, run if any terms
{
	//$_SESSION["SQLquery"] = $SQLquery;
	//$_SESSION["SQLquerySerial"] = $advsearch->Serialize();
	$SQLquerySerial = $advsearch->Serialize();
	$_SESSION["SQLquerySerial"] = $SQLquerySerial;	//save the new query to the session
	$page->redirect("advsearchresults.php?SQLquerySerial=$SQLquerySerial");
}
elseif ($new)									////new query button pressed
{
	$SQLquery = $advsearch->DeleteQuery();
	$advsearch->SetSortOrder();		//set back default sort order for new queries
}
elseif ($load)									////load button pressed
{
	//print($loadfrom);					//dropdown box with the saved queries
	//$serial="title|Bproduction_date|AAND|Bstation|Bis|BRadioSZTAKI|Bstation|AAND|Bproduction_date|Bbigger|B1035583200|Bdate|AOR|Bentry_date|Bbigger|B1035583200|Bdate|AAND|Btitle|Bdoes_not_contain|Bmusic|Bstring|AAND|Blanguage|Bis|Ben|Blang|AOR|Blanguage|Bis|Bde|Blang|AOR|Blanguage|Bis_not|Bhu|Blang";
	$prefs = $user->getPreferences();
	$serial=$prefs->savedQueries[$loadfrom]["query"];
	$SQLquery = $advsearch->Deserialize($serial);
}
elseif ($default)									////Make deafult button pressed
{
	//$loadfrom;					//dropdown box with the saved queries
	$prefs = $user->getPreferences();
	$savedQueries = $prefs->savedQueries;
	foreach($savedQueries as $key => $value) $prefs->savedQueries[$key]["default"] = 0;
	$prefs->savedQueries[$loadfrom]["default"] = 1;
	$prefs->save();
}
elseif ($deleteq)									////Delete query button pressed
{
	$prefs = $user->getPreferences();
	$prefs->savedQueries = array_merge(array_slice($prefs->savedQueries, 0, $loadfrom), array_slice($prefs->savedQueries, $loadfrom+1));
	$prefs->save();
	//print("<pre>");
	//var_dump($prefs->savedQueries);
	//print("</pre>");
}
elseif (($save) and $SQLquery!=NULL)						////save button pressed, save if any term
{
	$serial = $advsearch->Serialize();
	if ($saveas != "" AND $serial != "")
	{
		$prefs = $user->getPreferences();
		
		//$prefs->savedQueries =  array();
		$prefs->savedQueries[] = array("name" => $saveas,
						"query" => $serial,
						"default" => 0);
		$prefs->save();
		//print($saveas." = ".$serial);
	}
}
else								////- or + button pressed?
{
	$max = count($SQLquery);
	$i = 0;
	for(; $i < $max ;$i++)
	{
		//go through - buttons
		if ($paramcache->getRegistered("DEL".$i."_x") != NULL)
			$SQLquery = $advsearch->DelRow($i);
		//go through + buttons
		if ($paramcache->getRegistered("ADD".$i."_x") != NULL)
			$SQLquery = $advsearch->AddRow($SQLlink, $SQLfield, $i);
	}
}

$_SESSION["SQLquerySerial"] = $advsearch->Serialize();	//save the new query to the session

////SMARTY
//terms
$smarty->assign("SQLquery", $SQLquery);					//the query
$smarty->assign("SQLquerySerial", $advsearch->Serialize());		//the serialized query
$smarty->assign("SQLqueryfields", $advsearch->GetSQLqueryfields());	//translated name for all rows of the query

$smarty->assign("EQdate", $advsearch->GetEQdate());			//EQ dropdown for date
$smarty->assign("EQstring", $advsearch->GetEQstring());			//EQ dropdown for string
$smarty->assign("EQtopic", $advsearch->GetEQtopic());			//EQ dropdown for topics
$smarty->assign("EQlang", $advsearch->GetEQlang());			//EQ dropdown for lang
$smarty->assign("EQlength", $advsearch->GetEQlength());			//EQ dropdown for length
$smarty->assign("EQnumber", $advsearch->GetEQnumber());			//EQ dropdown for numbers

$smarty->assign("Languages", $advsearch->GetLanguages());		//all possible languages
$smarty->assign("Stations", $advsearch->GetStations());			//all possible stationnames
$smarty->assign("Genres", $advsearch->GetGenres());			//all possible genrenames
$smarty->assign("Ratings", $advsearch->getRatings());			//all possible ratings + a half value between all
$smarty->assign("SQLstring", $SQLstring);				//selected values

//box 1
$smarty->assign("SQLfields", $advsearch->GetSQLfields());		//name of all possibble columns
$smarty->assign("SQLfieldDefault", key($advsearch->GetSQLfields()));	//set default selected to the first element

//box 2
$smarty->assign("OrderFields", $advsearch->getOrderFields());		//name of all possibble columns
$smarty->assign("sort1", $advsearch->GetSort1());			//current sort 1
$smarty->assign("sort2", $advsearch->GetSort2());			//current sort 2
$smarty->assign("dir1", $advsearch->getDir1());			//current sort dir 1
$smarty->assign("dir2", $advsearch->getDir2());			//current sort dir 2

if ($portal_http == "") $portal_http = NULL;
if (isset($portal_http))
{
	if (substr($portal_http, 0, 7) != "http://") $portal_http = "http://".$portal_http;
	$smarty->assign("old_upload", $portal_http);	//save given URL (next time no nedd to write it again)
	$portal_http_new = str_replace("/portal.php/", "/portal_upload.php/", $portal_http);		//replace portal.php name with the php file responsible for upload
	if (strstr($portal_http_new, "/portal_upload.php/")) $file = @fopen ( $portal_http_new, "r");		//open file if string could be replaced
	if (!$file) $smarty->assign("upload_query", "http://");	//if not exist
	else	
		{
			$smarty->assign("upload_query", $portal_http_new);		//if exists
//			echo "OK";
			$_SESSION['portal_http'] = $portal_http;		//TODO save to user properties
		}
		$paramcache->setParameter('upload_http', NULL);		//so it will be processed only ones
}
else $smarty->assign("old_upload", $_SESSION['portal_http']);		//TODO load from user properties

//box 3
if ($user != "")	//only if logged in
{
	if (!isset($prefs)) $prefs = $user->getPreferences();
	$savedQueries = $prefs->savedQueries;
	$saved = array();
	foreach($savedQueries as $key => $value) $saved[$key] = $value["name"];
	if (count($saved) == 0) $saved = "";
	$smarty->assign("saved", $saved);					//saved fields
}
else $smarty->assign("notLoggedIn", true);

$paramcache->setProcessed();						//paramcache, against reload
//$paramcache->addResult("SQLquerySerial", $advsearch-Serialize());	//save serialized query

$page->send();

?>
