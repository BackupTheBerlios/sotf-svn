<?php
require("init.inc.php");
require("$classdir/sotf_AdvSearch.class.php");

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


	$query="SELECT id FROM sotf_programmes WHERE";			//begining of the SQL command
	$max = count($SQLquery);
	for($i = 0; $i < $max ;$i++)		//go through all terms
	{
		if ($i != 0) $query = $query." ".$SQLquery[$i][0];
		if ( (($SQLquery[$i][0] == "AND") || ($i == 0)) && ($SQLquery[$i+1][0] == "OR") ) $query = $query." (";	//set begining of round bracket
		if ($SQLquery[$i][4] == "date") $query = $query." ".$SQLquery[$i][1];		//field name
		else $query = $query." coalesce(".$SQLquery[$i][1].",'')";		//field name
		$set = false;
		switch ($SQLquery[$i][2]) {			//= < > != ...
		    case "bigger":
			$query = $query." >";
		        break;
		    case "smaller":
			$query = $query." <";
		        break;
		    case "is":
			$query = $query." =";
		        break;
		    case "contains":
			$query = $query." LIKE '%".$SQLquery[$i][3]."%'";
			$set = true;
		        break;
		    case "begins_with":
			$query = $query." LIKE '".$SQLquery[$i][3]."%'";
			$set = true;
		        break;
		    case "does_not_contain":
			$query = $query." NOT LIKE '%".$SQLquery[$i][3]."%'";
			$set = true;
		        break;
		    case "is_not_equal":
			$query = $query." !=";
		        break;
		    case "is_not":
			$query = $query." !=";
		        break;
		}
		
		if (!$set)
		{
			if ($SQLquery[$i][4] == "number") $query = $query." ".$SQLquery[$i][3];	//value
			elseif ($SQLquery[$i][4] == "date")
				{
					$date = getdate($SQLquery[$i][3]);
					$query = $query." '".$date["year"]."-".$date["mon"]."-".$date["mday"]."'";	//value
				}
			else $query = $query." '".$SQLquery[$i][3]."'";	//value
		}
		
		if (($SQLquery[$i][0] == "OR") && ($SQLquery[$i+1][0] != "OR")) $query = $query." )";		//set end of round bracket
	}
	$query = $query." ORDER BY ".$sort1.", ".$sort2;			//ISBN DESC, BOOK_TITLE 
//	print($query);

	$result = $db->getAll($query);
	$smarty->assign("result", $result);	//result array



$page->send();

?>
