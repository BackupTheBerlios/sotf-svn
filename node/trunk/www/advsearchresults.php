<?php
require("init.inc.php");
require("$classdir/sotf_AdvSearch.class.php");

$SQLquerySerial = sotf_Utils::getParameter('SQLquerySerial');			//the serialized query in the hidden field

$advsearch = new sotf_AdvSearch();						//create new search object object with this array

//if ($SQLquerySerial == "") print("SESSION");
//	else print("HIDDEN");

if ($SQLquerySerial == "")							//get old search query from session if none in hidden field	
	$SQLquerySerial = $_SESSION["SQLquerySerial"];				//get array from session

$SQLquery = $advsearch->Deserialize($SQLquerySerial);				//deserialize the content of the hidden field

if (sotf_Utils::getParameter('back') != NULL)					//if go back pressed
{
	$_SESSION["SQLquery"] = $SQLquery;					//save the new quey to the session
	$page->redirect("advsearch.php");
}


$query = $advsearch->GetSQLCommand();

$max = $db->getAll("SELECT count(*) FROM (".$query.") as count");	//get the number of results
$max = $max[0]["count"];

$limit = $page->resultspage($max, "$php_self?ID=$ID&NAME=$NAME");
$result = $db->getAll($query.$limit["limit"]);

$allfields = $advsearch->GetSQLfields();		//get all possible fileld names with translation

$max = count($SQLquery);				//$fields will contain all the USED field names
for($i =0; $i<$max; $i++)
	$fields[$SQLquery[$i][1]] = $allfields[$SQLquery[$i][1]];

if (array_key_exists("title", $fields))
{
	$fields[alternative_title] = $page->getlocalized("alternative_title");		//if title presented this two
	$fields[episode_title] = $page->getlocalized("episode_title");				//fields are needed as well
}
else $fields[title] = $page->getlocalized("title");		//the title field always need to be present


$selected = array();
$max = count($result);
for($i =0; $i<$max; $i++)	//$selected will contain all the information about the programmes that where present in the query
{
	foreach($result[$i] as $key => $value)
		if (array_key_exists($key, $fields) AND $key != 'title')		//title is presented on a diferent lavel
		if ($key == 'language' AND $value != "") $values[$fields[$key]] = $page->getlocalized($value);	//language need to be translated
		else $values[$fields[$key]] = $value;
	$item[title] = $result[$i][title];
	$item[id] = $result[$i][id];
	$item[icon] = $result[$i][icon];
	$item[values] = $values;
	$selected[] = $item;
	$item = "";
}
//var_dump($selected);

//if (DB::isError($result)) die($result->getMessage());
//print("<BR />".count($result));

$smarty->assign("SQLquery", $SQLquery);					//the query
$smarty->assign("SQLquerySerial", $advsearch->Serialize());		//the serialized query
//$smarty->assign("SQLqueryfields", $advsearch->GetSQLqueryfields());	//translated name for all fieldnames of the query
//$smarty->assign("SQLqueryEQs", $advsearch->GetSQLqueryEQs());		//translated name for all EQs (<, >, = ...) of the query
$smarty->assign("HumanReadable", $advsearch->GetHumanReadable());	//human readable format for the query fileds

$smarty->assign("result", $selected);						//result array

$page->send();

?>
