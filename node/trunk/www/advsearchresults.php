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


//print($query);
$result = $db->getAll($query);

//if (DB::isError($result)) die($result->getMessage());
//print("<BR />".count($result));

$smarty->assign("SQLquery", $SQLquery);					//the query
$smarty->assign("SQLquerySerial", $advsearch->Serialize());		//the serialized query
//$smarty->assign("SQLqueryfields", $advsearch->GetSQLqueryfields());	//translated name for all fieldnames of the query
//$smarty->assign("SQLqueryEQs", $advsearch->GetSQLqueryEQs());		//translated name for all EQs (<, >, = ...) of the query
$smarty->assign("HumanReadable", $advsearch->GetHumanReadable());	//human readable format for the query fileds
$smarty->assign("result", $result);						//result array

$page->send();

?>
