<?php
/*
 * This class represents a query for the advanced search
 *
 * @author Mate Pataki MTA SZTAKI DSD
 *
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

	function getEQSign($sign, $value)
	{
		switch ($sign) {			//= < > != ...
		    case "bigger":
			return " > ".$value;
		    case "smaller":
			return " < ".$value;
		    case "is":
			return " = ".$value;
		    case "is_equal":
			return " ~* '^".substr($value, 1, -1)."$'";
		    case "is_not_equal":
			return " != ".$value;
		    case "is_not":
			return " != ".$value;
		    case "contains":
			return " ~* '.*".substr($value, 1, -1).".*'";
		    case "begins_with":
			return " ~* '^".substr($value, 1, -1)."'";
		    case "does_not_contain":
			return " !~* '.*".substr($value, 1, -1).".*'";
		}
		return false;
	}

	function GetSQLCommand()			//gives back the SQL command for the search
	{
		global $lang;
		$query="SELECT distinct programmes.* FROM (";
		$query.=" SELECT sotf_programmes.*, sotf_stations.name as station, sotf_series.title as seriestitle, sotf_series.description as seriesdescription FROM sotf_programmes";
		$query.=" LEFT JOIN sotf_stations ON sotf_programmes.station_id = sotf_stations.id";
		$query.=" LEFT JOIN sotf_series ON sotf_programmes.series_id = sotf_series.id";
		$query.=") as programmes WHERE published = 't' AND";
		$max = count($this->SQLquery);					//all rows of the advsearch
		for($i = 0; $i < $max ;$i++)		//go through all terms
		{
			//AND or OR words
			if ($i != 0) $query .= " ".$this->SQLquery[$i][0];

			//set begining of round bracket
			if ( (($this->SQLquery[$i][0] == "AND") || ($i == 0)) && ($this->SQLquery[$i+1][0] == "OR") ) $query = $query." (";

			//field name eq sign and value
			if ($this->SQLquery[$i][4] == "date")
			{
				$query .= " ".$this->SQLquery[$i][1];
				$date = getdate($this->SQLquery[$i][3]);
				$query .= $this->getEQSign($this->SQLquery[$i][2], "'".$date["year"]."-".$date["mon"]."-".$date["mday"]."'");
			}
			elseif ($this->SQLquery[$i][1] == "topic")
			{
				$query .= " (sotf_programmes.id = sotf_prog_topics.prog_id".
					" and sotf_prog_topics.topic_id = sotf_topics.topic_id".
					" and sotf_topics.language = '$lang'".
					" and sotf_topics.topic_name";
				$query .= $this->getEQSign($this->SQLquery[$i][2], "'".$this->SQLquery[$i][3]."')");
			}
			elseif ($this->SQLquery[$i][1] == "title")
			{
				if (strpos($this->SQLquery[$i][2], "not") == false) $andor = "OR";
				else $andor = "AND";	//if does not contain or not equal then NONE should contain it
				$query .= " (coalesce(title,'')";
				$query .= $this->getEQSign($this->SQLquery[$i][2], "'".$this->SQLquery[$i][3]."'");
				$query .= " $andor coalesce(alternative_title,'')";
				$query .= $this->getEQSign($this->SQLquery[$i][2], "'".$this->SQLquery[$i][3]."'");
				$query .= " $andor coalesce(episode_title,'')";
				$query .= $this->getEQSign($this->SQLquery[$i][2], "'".$this->SQLquery[$i][3]."'").")";
			}
			elseif ($this->SQLquery[$i][1] == "person")
			{
				if ($this->SQLquery[$i][2] == "does_not_contain")
				{
					$qi2 = "contains";
					$not = "not";
				}
				elseif  ($this->SQLquery[$i][2] == "is_not_equal")
				{
					$qi2 = "is_equal";
					$not = "not";
				}
				else
				{
					$qi2 = $this->SQLquery[$i][2];
					$not = "";
				}

				$query .= " id $not in (SELECT sotf_object_roles.object_id as id FROM sotf_object_roles WHERE sotf_object_roles.contact_id = sotf_contacts.id AND";
				$query .= " ( coalesce(sotf_contacts.name,'')";
				$query .= $this->getEQSign($qi2, "'".$this->SQLquery[$i][3]."'");
				$query .= " OR coalesce(sotf_contacts.alias,'')";
				$query .= $this->getEQSign($qi2, "'".$this->SQLquery[$i][3]."'");
				$query .= " OR coalesce(sotf_contacts.acronym,'')";
				$query .= $this->getEQSign($qi2, "'".$this->SQLquery[$i][3]."'")."))";
			}
			elseif (($this->SQLquery[$i][4] == "number") or ($this->SQLquery[$i][4] == "genre"))
			{
				$query .= " coalesce(".$this->SQLquery[$i][1].",'')";
				$query .= $this->getEQSign($this->SQLquery[$i][2], $this->SQLquery[$i][3]);
			}
			else
			{
				$query .= " coalesce(".$this->SQLquery[$i][1].",'')";		//field name (coalesce => if value NULL terurns '')
				$query .= $this->getEQSign($this->SQLquery[$i][2], "'".$this->SQLquery[$i][3]."'");
			}

			//set end of round bracket
			if (($this->SQLquery[$i][0] == "OR") && ($this->SQLquery[$i+1][0] != "OR")) $query = $query." )";
		}
		$query = $query." ORDER BY ".$this->sort1.", ".$this->sort2;			//ISBN DESC, BOOK_TITLE 
		//print($query);
		//die();
		return $query;
	}


	function getPersons($program_id)			//gives back the persons that have to do with the program
	{
		global $db, $lang;
		$query="SELECT sotf_contacts.name, sotf_contacts.alias, sotf_contacts.acronym, sotf_role_names.name as role FROM sotf_contacts, sotf_role_names WHERE sotf_contacts.id = sotf_object_roles.contact_id AND sotf_object_roles.object_id = '$program_id' AND sotf_object_roles.role_id = sotf_role_names.role_id AND sotf_role_names.language='$lang'";
		return $db->getAll($query);
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
		    case "topic":
			$new[4] = "string";
		        break;
		    case "entry_date":
			$new[4] = "date";
		        break;
		    case "expiry_date":
			$new[4] = "date";
		        break;
		    case "modify_date":
			$new[4] = "date";
		        break;
		    case "broadcast_date":
			$new[4] = "date";
		        break;
		    case "owner":
			$new[4] = "string";
		        break;
		    case "person":
			$new[4] = "string";
		        break;
		    case "title":
			$new[4] = "string";
		        break;
		    case "seriestitle":
			$new[4] = "string";
		        break;
		    case "seriesdescription":
			$new[4] = "string";
		        break;
		    case "keywords":
			$new[4] = "string";
		        break;
		    case "genre_id":
			$new[4] = "genre";
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
		    case "spatial_coverage":
			$new[4] = "string";
		        break;
		    case "temporal_coverage":
			$new[4] = "date";
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
	
	function GetHumanReadable()			//translates fieldnames for all rows of the query
	{
		global $page;
		$SQLfields = "";
		$max = count($this->SQLquery);
		for($i=0; $i < $max; $i++)
			{
			$SQLfield[0] = $page->getlocalized($this->SQLquery[$i][0]);
			$SQLfield[1] = $page->getlocalized($this->SQLquery[$i][1]);
			$SQLfield[2] = $page->getlocalized($this->SQLquery[$i][2]);
			if ($this->SQLquery[$i][4] == "date") $SQLfield[3] = date("Y-m-d", $this->SQLquery[$i][3]);
				elseif ($this->SQLquery[$i][4] == "lang") $SQLfield[3] = $page->getlocalized($this->SQLquery[$i][3]);
				else $SQLfield[3] = $this->SQLquery[$i][3];
			$SQLfield[4] = $this->SQLquery[$i][4];
			$SQLfields[] = $SQLfield;
			}
		return $SQLfields;
	}

	function GetSQLqueryfields()			//translates fieldnames for all rows of the query
	{
		global $page;
		$max = count($this->SQLquery);
		for($i=0; $i < $max; $i++)
			$SQLfield[] = $page->getlocalized($this->SQLquery[$i][1]);
		return $SQLfield;
	}

/*
	function GetSQLqueryEQs()			//translates EQs (=, <, >...) for all rows of the query
	{
		global $page;
		$max = count($this->SQLquery);
		for($i=0; $i < $max; $i++)
			$SQLEQfiels[] = $page->getlocalized($this->SQLquery[$i][2]);
		return $SQLEQfiels;
	}
*/

	function GetSQLfields()		//translates fieldnames for dropdown box
	{
		global $page;
		$SQLfiels[station] = $page->getlocalized("station");
		$SQLfiels[production_date] = $page->getlocalized("production_date");
		$SQLfiels[language] = $page->getlocalized("language");
		$SQLfiels[person] = $page->getlocalized("person");
		$SQLfiels[title] = $page->getlocalized("title");
		$SQLfiels[seriestitle] = $page->getlocalized("seriestitle");
		$SQLfiels[topic] = $page->getlocalized("topic");
		$SQLfiels[length] = $page->getlocalized("length");
		$SQLfiels[series] = $page->getlocalized("series");
		$SQLfiels[track] = $page->getlocalized("track");
		$SQLfiels[owner] = $page->getlocalized("owner");
		$SQLfiels[genre_id] = $page->getlocalized("genre_id");
		$SQLfiels[keywords] = $page->getlocalized("keywords");
		$SQLfiels[abstract] = $page->getlocalized("abstract");
		$SQLfiels[seriesdescription] = $page->getlocalized("seriesdescription");
		$SQLfiels[entry_date] = $page->getlocalized("entry_date");
		$SQLfiels[expiry_date] = $page->getlocalized("expiry_date");
		$SQLfiels[modify_date] = $page->getlocalized("modify_date");
		$SQLfiels[broadcast_date] = $page->getlocalized("broadcast_date");
		$SQLfiels[spatial_coverage] = $page->getlocalized("spatial_coverage");
		$SQLfiels[temporal_coverage] = $page->getlocalized("temporal_coverage");
		$SQLfiels[contact_email] = $page->getlocalized("contact_email");
		$SQLfiels[contact_phone] = $page->getlocalized("contact_phone");
		return $SQLfiels;
	}

	function GetLanguages()		//returns all the languages
	{
		global $page, $languages;
		$max = count($languages);
		for($i=0; $i<$max;$i++) $Languages[$languages[$i]] = $page->getlocalized($languages[$i]);
		return $Languages;
	}

	function GetStations()		//returns all the stations
	{
		$stationsarray = sotf_Station::listStationNames();
		$max = count($stationsarray);
		for($i=0; $i<$max;$i++) $Stations[$stationsarray[$i][name]] = $stationsarray[$i][name];
		return $Stations;
	}

	function GetGenres()		//returns all the genres
	{
		global $repository;
		$genresarray = $repository->getGenres();
		$max = count($genresarray);
		for($i=0; $i<$max;$i++) $Genres[$genresarray[$i][id]] = $genresarray[$i][name];
		return $Genres;
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
		$EQstring[is_equal] = $page->getlocalized("is");
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

	function simpleSearch($words, $language = false)		//searches the words in the most popular fields
	{
		$words = htmlspecialchars($words);
		$word = split(" ", $words);
		$max = count($word);
		for ($i=0; $i<$max; $i++)
		{
			$word = trim($word);
			if ($word == "") next;
			$serial = str_replace("XXX", $word[$i], "production_date|Bstation|AAND|Bperson|Bcontains|BXXX|Bstring|AOR|Btitle|Bcontains|BXXX|Bstring|AOR|Bkeywords|Bcontains|BXXX|Bstring|AOR|Babstract|Bcontains|BXXX|Bstring|AOR|Bspatial_coverage|Bcontains|BXXX|Bstring");
			if ($language) $serial .= "|AAND|Blanguage|Bis|B".$language."|Blang"
			$this->Deserialize($serial);
			$query = $this->GetSQLCommand();
			print($query."<br><br>")
		}
	}

}
?>