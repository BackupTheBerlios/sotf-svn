<?php
/**
* This class represents a query for the advanced search
*
* @uthor Mate Pataki MTA SZTAKI DSD
*
* @package streamonthefly/node
*/
class sotf_AdvSearch
{
	var $SQLquery, $sort1, $sort2;
	
	function sotf_AdvSearch($array = "")			//constuctor with a starting query
	{
		if ($array != "") $this->SQLquery = $array;
	}

	function GetQuery()					//gives back the current query
	{
		return $this->SQLquery;
	}

	function GetSort1()					//gives back the current query
	{
		return $this->sort1;
	}

	function GetSort2()					//gives back the current query
	{
		return $this->sort2;
	}

	function DeleteQuery()					//start a new query
	{
		$this->SQLquery = NULL;
		return $this->SQLquery;
	}

	function Serialize()					//make a string from array
	{
		$serial= $this->sort1."|B".$this->sort2;
		$max = count($this->SQLquery);
		for($i=0; $i < $max; $i++)
		{			//Need | char as a sepecial char so replace it
			$serial = $serial."|A".strtr($this->SQLquery[$i][0], "|", "I")."|B".strtr($this->SQLquery[$i][1], "|", "I")."|B".strtr($this->SQLquery[$i][2], "|", "I")."|B".strtr($this->SQLquery[$i][3], "|", "I")."|B".strtr($this->SQLquery[$i][4], "|", "I");
		}
		return $serial;
	}

	function Deserialize($serial)					//make an array from string
	{
		$this->SQLquery = "";
		$terms = explode("|A", $serial);
		$max = count($terms);
		$term = explode("|B", $terms[0]);		//sort order is the first array
		$this->SetSortOrder($term[0], $term[1]);	//SetSortOrder
		for($i=1; $i < $max; $i++)
		{			//Need | char as a sepecial char so replace it
			$term = explode("|B", $terms[$i]);
			if (count($term == 5))			//to be sure :-)
				$this->SQLquery[]=$term;
		}
		return $this->SQLquery;
	}

	function AddRow($SQLlink, $SQLfield, $where = -1)			//add a row to the query
	{
		if ($SQLlink == "AND") $new[0] = "AND";
		else $new[0] = "OR";
		$new[1] = $SQLfield;		//name of the field
		$new[2] = "";			//=, !=, <, >
		$new[3] = "";			//text or date

		switch ($SQLfield) {		//set type of the field
		    case "station":
			$new[4] = "station";
		        break;
		    case "production_date":
			$new[4] = "date";
		        break;
		    case "language":
			$new[4] = "lang";
		        break;
		    case "series":
			$new[4] = "string";
		        break;
		    case "track":
			$new[4] = "string";
		        break;
		    case "entry_date":
			$new[4] = "date";
		        break;
		    case "expiry_date":
			$new[4] = "date";
		        break;
		    case "owner":
			$new[4] = "string";
		        break;
		    case "author":
			$new[4] = "string";
		        break;
		    case "title":
			$new[4] = "string";
		        break;
		    case "keywords":
			$new[4] = "string";
		        break;
		    case "abstract":
			$new[4] = "string";
		        break;
		    case "length":
			$new[4] = "length";
		        break;
		    case "contact_email":
			$new[4] = "string";
		        break;
		    case "contact_phone":
			$new[4] = "string";
		        break;
		}

		if ($where == -1)
		{
			$this->SQLquery[] = $new;
		}
		else
		{
			$max = count($this->SQLquery);
			$output1 = array_slice($this->SQLquery, 0, $where+1);   
			if ($where < $max) $output2 = array_slice($this->SQLquery, $where+1);
			//var_dump($output);
			$output1[]=$new;
			$this->SQLquery = array_merge($output1, $output2);
		}
		return $this->SQLquery;
	}
	
	function DelRow($where)		//set the sort order
	{
		$max = count($this->SQLquery);
		if ($where > 0) $output1 = array_slice($this->SQLquery, 0, $where);
		if ($where < $max) $output2 = array_slice($this->SQLquery, $where+1);
		$this->SQLquery = array_merge($output1, $output2);
		return $this->SQLquery;
	}

	function SetSortOrder($sort1 = "", $sort2 = "")		//set the sort order
	{
		if ($sort1 == "") $this->sort1 = "production_date";
		else $this->sort1 = $sort1;
		if ($sort2 == "") $this->sort2 = "station";
		else $this->sort2 = $sort2;
	}
	
	function GetSQLqueryfields($SQLquery)		//translates fieldnames for all rows of the query
	{
		global $page;
		$max = count($SQLquery);
		for($i=0; $i < $max; $i++)
			$SQLfiels[] = $page->getlocalized($SQLquery[$i][1]);
		return $SQLfiels;
	}

	function GetSQLfields()		//translates fieldnames for dropdown box
	{
		global $page;
		$SQLfiels[station] = $page->getlocalized("station");
		$SQLfiels[production_date] = $page->getlocalized("production_date");
		$SQLfiels[language] = $page->getlocalized("language");
		$SQLfiels[author] = $page->getlocalized("author");
		$SQLfiels[title] = $page->getlocalized("title");
		$SQLfiels[keywords] = $page->getlocalized("keywords");
		$SQLfiels[length] = $page->getlocalized("length");
		$SQLfiels[series] = $page->getlocalized("series");
		$SQLfiels[track] = $page->getlocalized("track");
		$SQLfiels[entry_date] = $page->getlocalized("entry_date");
		$SQLfiels[owner] = $page->getlocalized("owner");
		$SQLfiels[abstract] = $page->getlocalized("abstract");
		$SQLfiels[contact_email] = $page->getlocalized("contact_email");
		$SQLfiels[contact_phone] = $page->getlocalized("contact_phone");
		$SQLfiels[expiry_date] = $page->getlocalized("expiry_date");
		return $SQLfiels;
	}

	function GetLanguages()		//returns all the languages
	{
		global $page, $languages;
		$max = count($languages);
		for($i=0; $i<$max;$i++) $Languages[$languages[$i]] = $page->getlocalized($languages[$i]);
		return $Languages;
	}

	function GetEQdate()		//returns EQ options for dates
	{
		global $page;
		$EQdate[bigger] = $page->getlocalized("after");
		$EQdate[smaller] = $page->getlocalized("before");
		$EQdate[is] = $page->getlocalized("is");
		$EQdate[is_not] = $page->getlocalized("is_not");
		return $EQdate;
	}

	function GetEQstring()		//returns EQ options for strings
	{
		global $page;
		$EQstring[contains] = $page->getlocalized("contains");
		$EQstring[begins_with] = $page->getlocalized("begins_with");
		$EQstring[equals] = $page->getlocalized("is");
		$EQstring[does_not_contain] = $page->getlocalized("does_not_contain");
		$EQstring[is_not_equal] = $page->getlocalized("is_not_equal");
		return $EQstring;
	}

	function GetEQlang()		//returns EQ options for languages and station
	{
		global $page;
		$EQlang[is] = $page->getlocalized("is");
		$EQlang[is_not] = $page->getlocalized("is_not");
		return $EQlang;
	}

	function GetEQlength()		//returns EQ options for length
	{
		global $page;
		$EQlength[bigger] = $page->getlocalized("longer");
		$EQlength[smaller] = $page->getlocalized("shorter");
		$EQlength[is] = $page->getlocalized("is");
		$EQlength[is_not] = $page->getlocalized("is_not");
		return $EQlength;
	}

}
?>