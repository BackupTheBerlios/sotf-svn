<?php
require("init.inc.php");
require("$classdir/sotf_AdvSearch.class.php");

//1. Add terms to the query
$SQLlink = sotf_Utils::getParameter('SQLlink');			//linker (OR or AND)
$SQLfield = sotf_Utils::getParameter('SQLfield');		//name ot the term to add
$add = sotf_Utils::getParameter('add');				//add term button
$new = sotf_Utils::getParameter('new');				//new query button

//2. Run query
$sort1 = sotf_Utils::getParameter('sort1');			//sort order 1
$sort2 = sotf_Utils::getParameter('sort2');			//sort order 2
$run = sotf_Utils::getParameter('run');				//run query button
$run_image = sotf_Utils::getParameter('image_x');		//TRANSPARENT_run query button (default by enter)

//3. Manage your queries
$loadfrom = sotf_Utils::getParameter('loadfrom');		//dropdown box with the saved queries
$load = sotf_Utils::getParameter('load');			//load button
$default = sotf_Utils::getParameter('default');			//make my default button
$deleteq = sotf_Utils::getParameter('deleteq');			//delete query button
$save = sotf_Utils::getParameter('save');			//save button
$saveas = sotf_Utils::getParameter('saveas');			//text field

//Current query
$SQLeq = sotf_Utils::getParameter('SQLeq');			//= < > ... values array
$SQLstring = sotf_Utils::getParameter('SQLstring');		//last parameter value array
$SQLquerySerial = sotf_Utils::getParameter('SQLquerySerial');	//the serialized query

if ($SQLquerySerial == "")			//get old search query from session if none in hidden field	
{
	$SQLquery = $_SESSION["SQLquery"];		//get array from session
	$advsearch = new sotf_AdvSearch($SQLquery);	//create search object object with this array
}
else 						//else get it from hidden field
{
	$advsearch = new sotf_AdvSearch();		//create new search object object
	$SQLquery = $advsearch->Deserialize($SQLquerySerial);	//deserialize the content of the hidden field
}

if ($SQLquery == NULL) $advsearch->SetSortOrder("", "");	//set default sort order for new queries
else $advsearch->SetSortOrder($sort1, $sort2);			//set sort order

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
	$_SESSION["SQLquery"] = $SQLquery;
	$_SESSION["SQLquerySerial"] = $advsearch->Serialize();

	$page->redirect("advsearchresults.php");
}
elseif ($new)									///new query button pressed
{
	$SQLquery = $advsearch->DeleteQuery();
	$advsearch->SetSortOrder("", "");		//set back default sort order for new queries
}
elseif (($save) and $SQLquery!=NULL)						////save button pressed, save if any term
{
$serial = $advsearch->Serialize();
print($serial);
$SQLquery = $advsearch->Deserialize($serial);
}
elseif ($load)									///load button pressed
{
$serial="title|Bproduction_date|AAND|Bstation|Bis|BRadioSZTAKI|Bstation|AAND|Bproduction_date|Bbigger|B1035583200|Bdate|AOR|Bentry_date|Bbigger|B1035583200|Bdate|AAND|Btitle|Bdoes_not_contain|Bmusic|Bstring|AAND|Blanguage|Bis|Ben|Blang|AOR|Blanguage|Bis|Bde|Blang|AOR|Blanguage|Bis_not|Bhu|Blang";
$SQLquery = $advsearch->Deserialize($serial);
}
else										////- or + button pressed?
{
	$max = count($SQLquery);
	$i = 0;
	for(; $i < $max ;$i++)
	{
		//go through - buttons
		if (sotf_Utils::getParameter("DEL".$i."_x") != NULL)
			$SQLquery = $advsearch->DelRow($i);
		//go through + buttons
		if (sotf_Utils::getParameter("ADD".$i."_x") != NULL)
			$SQLquery = $advsearch->AddRow($SQLlink, $SQLfield, $i);
	}
}


$_SESSION["SQLquery"] = $SQLquery;				//save the new quey to the session

////SMARTY
//terms
$smarty->assign("SQLquery", $SQLquery);					//the query
$smarty->assign("SQLquerySerial", $advsearch->Serialize());		//the serialized query
$smarty->assign("SQLqueryfields", $advsearch->GetSQLqueryfields());	//translated name for all rows of the query

$smarty->assign("EQdate", $advsearch->GetEQdate());			//EQ dropdown for date
$smarty->assign("EQstring", $advsearch->GetEQstring());			//EQ dropdown for string
$smarty->assign("EQlang", $advsearch->GetEQlang());			//EQ dropdown for lang
$smarty->assign("EQlength", $advsearch->GetEQlength());			//EQ dropdown for length

$smarty->assign("Languages", $advsearch->GetLanguages());		//all possible languages
$smarty->assign("Stations", $advsearch->GetStations());		//all possible stationnames
$smarty->assign("SQLstring", $SQLstring);				//selected values

//box 1
$smarty->assign("SQLfields", $advsearch->GetSQLfields());		//name of all possibble columns
$smarty->assign("SQLfieldDefault", key($advsearch->GetSQLfields()));	//set default selected to the first element

//box 2
$smarty->assign("sort1", $advsearch->GetSort1());			//current sort1
$smarty->assign("sort2", $advsearch->GetSort2());			//current sort2

//box 3
$smarty->assign("saved", $saved[example]="Example");			//saved fields

$page->send();

?>
