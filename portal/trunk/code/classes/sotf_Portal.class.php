<?php
/*
 * This class represents a portal outlook
 *
 * @author Mate Pataki MTA SZTAKI DSD
 *
 */

require_once("$classdir/rpc_Utils.class.php");

class sotf_Portal
{
	var $settings, $portal_id, $portal_name, $portal_admin, $portal_password, $programmes_on_portal;
	
	function sotf_Portal($portal_name)			//constuctor
	{
		global $db;

		$this->portal_name = $portal_name;
		$query = "SELECT portal_settings.id, admin_id, portal_settings.password"
			." FROM portal_settings WHERE portal_settings.name = '$portal_name'";
		$result = $db->getRow($query);

		if ($result != NULL)
		{
			$this->portal_admin = $result['admin_id'];
			$this->portal_password = $result['password'];
			$this->portal_id = $result['id'];
		}
		else $this->portal_id = NULL;
	}


	function getId()
	{
		return $this->portal_id;	//return portal_id
	}

	function getTable()
	{
		return $this->settings['table'];
	}

	function getCell($row, $col)
	{
		return $this->settings['table'][$row][$col];
	}

	function getProgrammesOnPortal()
	{
		if (count($this->programmes_on_portal) > 0) return $this->programmes_on_portal;
		return array();
	}
	
	function loadSettings()	//load from fatabase
	{
		global $db;

		$query = "SELECT settings FROM portal_settings WHERE id = $this->portal_id";
		$result = $db->getRow($query);
		if ($result['settings'] == NULL)			//new portal, never saved
		{
			$query = "SELECT settings FROM portal_templates WHERE name = 'Under construction'";
			$result = $db->getRow($query);
		}
		
		$settings = unserialize(base64_decode($result['settings']));
		$this->setSettings($settings);
		return $settings;
	}

	function saveSettings($settings)	//save to database
	{
		global $db;
		$a = base64_encode(serialize($settings));
		$sql="UPDATE portal_settings SET settings='$a' WHERE id='$this->portal_id'";
//		$sql="INSERT INTO portal_templates(name, settings, published) values('Under construction', '$a', false)";
		return $db->query($sql);
	}

	function setSettings($settings)		//set settings in the object
	{
		if ($settings['table'] == "") return 0;
		foreach ($settings['table'] as $row_number => $row)		//go throug all cells and set html part as well
		{
			foreach ($row as $col_number => $cell)
				$this->setCell($row_number, $col_number, $cell);
		}
		$this->searchOldEvents();		//search for programmes and queries that are removed, and remove them from the statistics
	}

	function isAdmin($id)
	{
		return ($this->portal_admin == $id);	//true if $id is admin on this site
	}

	function setCell($row, $col, $cell)
	{
		foreach ($cell as $key => $value) $cell[$key] = trim($cell[$key]);

		if ($cell["link"] == "") $cell["link"] = "none";
		if ($cell["style"] == "") $cell["style"] = "none";
		if ($cell["class"] == "") $cell["class"] = "none";

		if ($cell["align"] == "") $cell["align"] = "center";		//default value
		if ($cell["valign"] == "") $cell["valign"] = "middle";		//default value
		//if ($cell["width"] == "") $cell["width"] = "";		//default value
		if ($cell["color"] == "") $cell["color"] = "";			//default value
		else $cell["color"] = $this->correctColor($cell["color"]);	//else check value

		$cur_style = array();
		if ($cell["style"] != "none")
		{
			$s1 = split("\|", $cell["style"]);
			foreach($s1 as $s2)
			{
				$s3 = split(":", $s2);
				$cur_style[$s3[0]] = $s3[1];
			}
			$cell['curr_style'] = $cur_style;
		}

		$this->settings['table'][$row][$col]["resource"] = $cell["resource"];
		$this->settings['table'][$row][$col]["value"] = $cell["value"];
		$this->settings['table'][$row][$col]["link"] = $cell["link"];
		$this->settings['table'][$row][$col]["style"] = $cell["style"];
		$this->settings['table'][$row][$col]["class"] = $cell["class"];
		$this->settings['table'][$row][$col]["align"] = $cell["align"];
		$this->settings['table'][$row][$col]["valign"] = $cell["valign"];
		$this->settings['table'][$row][$col]["width"] = $cell["width"];
		$this->settings['table'][$row][$col]["color"] = $cell["color"];

		$html = "";

		if ($cell["resource"] == "picture")
		{
			/*
			if ($cell["link"] != "none") $html .= "<a href='".$cell["link"]."'>";
			$html .= "<img src=\"".$cell["value"]."\" border=\"0\"";
			if ($cell["class"] != "none") $html .= " class=\"".$cell["class"]."\"";
			$html .= ">";
			if ($cell["link"] != "none") $html .= "</a>";
			*/
		}
		elseif ($cell["resource"] == "text")
		{
			$html = nl2br($cell["value"]);			//convert \n to <br />
			$this->settings['table'][$row][$col]['html'] = $html;
			/*
			if ($cell["class"] != "none") $html .= "<span class=\"".$cell["class"]."\">";
			if ($cell["style"] != "none")
			{
				$html .= "<font";
				if ($cur_style["color"] != "") $html .= " color=\"".$cur_style["color"]."\"";
				if ($cur_style["face"] != "") $html .= " face=\"".$cur_style["face"]."\"";
				if ($cur_style["size"] != "") $html .= " size=\"".$cur_style["size"]."\"";
				$html .= ">";
			}
			if ($cell["link"] != "none") $html .= "<a href=\"".$cell["link"]."\">";
			$html .= nl2br($cell["value"]);
			if ($cell["link"] != "none") $html .= "</a>";
			if ($cell["style"] != "none") $html .= "</font>";
			if ($cell["class"] != "none") $html .= "</span>";
			*/
		}
		elseif (($cell["resource"] == "query") OR ($cell["resource"] == "playlist"))
		{
			global $IMAGEDIR, $db, $page;


			if ($cell["resource"] == "query")
			{
				$this->addEvent("query", $cell["value"]);
				$results = $this->runQuery($cell["value"]);
			}
			else $results = $this->runPlaylist($cell["value"]);

			if (!(count($results) > 0)) $results = array();		//if no results create empty array();

			$selected_result = array();

			$fields = $this->getAllFieldnames();


			foreach($results as $result)
			{
				$this->addEvent("programme", $result['id']);
				$this->programmes_on_portal[$result['id']] = $result;		//collecting for programmes editor page
				$prgprop = $this->getPrgProperties($result['id']);
				$item['teaser'] = $prgprop['teaser'];
				$item['text'] = $prgprop['text'];
				$item['files'] = $prgprop['files'];
				$item['comments'] = $prgprop['comments'];
				$item['listen'] = array();

				foreach($result as $key => $value)
					if (array_key_exists($key, $fields) AND $key != 'title')		//title is presented on a diferent level
						if ($key == 'language' AND $value != "")			//language need to be translated
						{
							$languages = explode(',', $value);
							foreach ($languages as $language)
							{
								if ($values[$fields[$key]] == "") $values[$fields[$key]] .= $page->getlocalized($language);
								else $values[$fields[$key]] .= ", ".$page->getlocalized($language);
							}
						}
						else $values[$fields[$key]] = htmlspecialchars($value);
				foreach($result['audioFiles'] as $audioFiles)
				{
					$file['mime_type'] = $audioFiles['mime_type'];
					$file['link'] = "listen.php/audio.m3u?id=".$audioFiles['prog_id']."&fileid=".$audioFiles['id'];
					$file['filesize'] = $audioFiles['filesize'];
					$file['play_length'] = $audioFiles['play_length'];
					$file['kbps'] = $audioFiles['kbps'];
					$file['vbr'] = $audioFiles['vbr'];
					$item['listen'][] = $file;
				}

				$item['title'] = htmlspecialchars($result['title']);
				$item['id'] = $result['id'];
				$item['icon'] = $result['icon'];
				$item['values'] = $values;
				$selected_result[] = $item;
				$item = "";
				$values = "";

			}

			$this->settings['table'][$row][$col]['items'] = $selected_result;
		}
	}

	function insertCell($row, $col, $flag)
	{
		$cur_row = $this->settings['table'][$row];
		$max = count($cur_row);

		if ($flag == "after")
		{
			for($i=$max-1; $i>$col; $i--)
				$cur_row[$i+1] = $cur_row[$i];
			$cur_row[$col+1] = array('resource' => 'text',
							'value' => '',
							'link' => '',
							'style' => '',
							'class' => '',
							'align' => '',
							'valign' => '',
							'width' => '');
		}
		if ($flag == "before")
		{
			for($i=$max-1; $i>$col-1; $i--)
				$cur_row[$i+1] = $cur_row[$i];
			$cur_row[$col] = array('resource' => 'text',
							'value' => '',
							'link' => '',
							'style' => '',
							'class' => '',
							'align' => '',
							'valign' => '',
							'width' => '');
		}
		$this->settings['table'][$row] = $cur_row;
	}

	function deleteCell($row, $col)
	{
		$cur_row = $this->settings['table'][$row];
		$max = count($cur_row);

		if ($max == 1)
		{
			$this->deleteRow($row);
			return 1;
		}

		$cur_row1 = array();
		$cur_row2 = array();
		if ($col != 0) $cur_row1 = array_slice($cur_row, 0, $col);
		if ($col != $max) $cur_row2 = array_slice($cur_row, $col+1);
		$cur_row = array_merge($cur_row1, $cur_row2);

		$this->settings['table'][$row] = $cur_row;
	}
	
	function deleteRow($row)
	{
		$rows = $this->settings['table'];
		$max = count($rows);

		$rows1 = array();
		$rows2 = array();
		if ($row != 0) $rows1 = array_slice($rows, 0, $row);
		if ($row != $max) $rows2 = array_slice($rows, $row+1);
		$rows = array_merge($rows1, $rows2);
		$this->settings['table'] = $rows;
	}

	function insertRow($row)
	{
		$new_cell = array('resource' => 'text',
				'value' => '',
				'link' => '',
				'style' => '',
				'class' => '',
				'align' => '',
				'valign' => '',
				'width' => '');
		$rows = $this->settings['table'];
		$max = count($rows);

		$rows1 = array();
		$rows2 = array();
		if ($row != -1) $rows1 = array_slice($rows, 0, $row+1);
		if ($row != $max) $rows2 = array_slice($rows, $row+1);
		$rows = array_merge($rows1, array(0 => array(0 => $new_cell)), $rows2);
		$this->settings['table'] = $rows;
	}

	function getResources()
	{
		return array(	'text'=>'text',
				'picture'=>'picture',
				'query'=>'query',
				'playlist'=>'static programme list',
				'space'=>'space');
	}

	function uploadFile($filename, $name, $prg_id = NULL, $custom_name = NULL)
	{
		global $db;
		if (!file_exists($filename)) return false;
		$newdirname = $_SERVER['DOCUMENT_ROOT'].str_replace('portal.php', "", $_SERVER["SCRIPT_NAME"]).$this->portal_name;
		$newfilename = $newdirname."/".$name;
		$i = 0;
		$dot = strrpos($name, '.');
		$ext = substr($name, $dot);
		$base = substr($name, 0, $dot);
		while(file_exists($newfilename))
		{
			$i++;
			$name = $base."_".(string)$i.$ext;
			$newfilename = $newdirname."/".$name;
		}
		if (!file_exists($newdirname)) mkdir($newdirname, 0755);
		move_uploaded_file($filename, $newfilename);
		chmod($newfilename, 0755);
		$url = str_replace($_SERVER["DOCUMENT_ROOT"], '', $newfilename);

		if ($custom_name != NULL) $name = $custom_name;
		if ($prg_id != NULL AND file_exists($newfilename))
		{
			$sql="INSERT INTO portal_files(portal_id, progid, file_location, filename) values('$this->portal_id', '$prg_id', '$url', '$name')";
			$db->query($sql);
		}
		if ($prg_id == NULL AND file_exists($newfilename))
		{
			$sql="INSERT INTO portal_files(portal_id, file_location, filename) values('$this->portal_id', '$url', '$name')";
			$db->query($sql);
		}

	}

	function deleteFile($filename, $prg_id = NULL)
	{
		global $db;
		if ($prg_id == NULL)
		{
		}
		else
		{
			if (@unlink($_SERVER["DOCUMENT_ROOT"].$filename))
			{
				$sql="DELETE FROM portal_files WHERE portal_id = $this->portal_id AND file_location = '$filename' AND progid = '$prg_id'";
				return $db->query($sql);
			}
			else return false;
		}
	}

	function getUploadedFiles()
	{
		global $page, $db;

		$sql="SELECT file_location, filename FROM portal_files WHERE portal_id = '$this->portal_id'";
		$result = $db->getAll($sql);

		$files = array();
		if ($result == NULL) return $files;
		foreach ($result as $file) $files[$file['file_location']] = $file['filename'];

//		$files = array(	"static/next.png" => "next.png",
//				"http://www.pbs.org/kratts/world/aust/kangaroo/images/kangaroo.jpg" => "kangaroo.jpg",
//				"http://www.dsd.sztaki.hu/~mate/pm205/bg.jpg" => "bg.jpg",
//				"http://dsd.sztaki.hu/belsolap_components/belsolap.css" => "dsd.css",
//				"http://members.iinet.net.au/~oneilg/scouts/pix/badges/scout/patrol/kangaroo.jpg" => "kangaroo2.jpg");
//		return $files;
		return array_merge(array("none" => $page->getlocalized("choose")), $files);
	}

	function getStyles()
	{
		/*
		<FONT color="">White , Silver Gray Black Yellow Red Blue 
		<FONT FACE="">
		Serif fonts: Times New Roman, Courier New, Georgia 
		Non-serif fonts: Arial, Arial Black, Verdana, Comic Sans, Trebuchet MS, Impact, Helvetica, Geneva 
		Symbol Fonts: WebDings 
		<FONT SIZE="">
		SIZE="0" 
		SIZE="-3" 
		*/
		return array(	"none" => "none",
				"color:red|size:+2" => "red+2",
				"size:+3" => "+3",
				"size:+2" => "+2",
				"size:+1" => "+1",
				"face:arial" => "arial",
				"size:5|face:Arial, Helvetica, sans-serif" => "title");
	}

	function getAligns()
	{
		return array(	"center" => "Center",
				"left" => "Left",
				"right" => "Right",
				"justify" => "Justify");
	}

	function getValigns()
	{
		return array(	"middle" => "Middle",
				"top" => "Top",
				"bottom" => "Bottom");
	}

	function getQueries()
	{
		global $db;
		$queries = array();

		$sql="SELECT name, query FROM portal_queries WHERE portal_id = '$this->portal_id'";
		$result = $db->getAll($sql);
		$queries = array();		
		foreach ($result as $query) $queries[$query['query']] = $query['name'];
		return $queries;
	}

	function getAllFieldnames()
	{
		global $page;
		$fields[title] = $page->getlocalized("title");
		$fields[alternative_title] = $page->getlocalized("alternative_title");
		$fields[episode_title] = $page->getlocalized("episode_title");
		$fields[seriestitle] = $page->getlocalized("seriestitle");
		$fields[broadcast_date] = $page->getlocalized("broadcast_date");
		$fields[station] = $page->getlocalized("station");
		$fields[language] = $page->getlocalized("language");
		//$fields[length] = $page->getlocalized("length");
		return $fields;
	}

	function runQuery($query)
	{
		return $this->getCached($query, 1);	//1 means query
	}

	function getCached($query, $type)	//$type 1 is query 2 is programme
	{
		global $sotfSite, $db, $REFRESH_TIME, $ERROR_REFRESH_TIME;

		$sql = "SELECT timestamp, value FROM portal_cache WHERE type=$type AND name='$query'";
		$cached = $db->getRow($sql);
		//if refresh needed
		if (($cached === NULL) OR ($db->diffTimestamp($db->getTimestamp(), $cached['timestamp']) > $REFRESH_TIME))
		{
			$result = NULL;
			//if failed to connect in the last $ERROR_REFRESH_TIME seconds don't try again
			$sql = "SELECT timestamp FROM portal_statistics WHERE name='last_connection_try'";
			$last_error = $db->diffTimestamp($db->getTimestamp(), $db->getOne($sql));
			if ($last_error > $ERROR_REFRESH_TIME)
			{
				//echo("connect...");
				$rpc = new rpc_Utils;
				$url = $sotfSite."xmlrpcServer.php";
				$objs = array($query);

				if ($type == 1) $result = $rpc->call($url, 'portal.query', $objs);
				else $result = $rpc->call($url, 'portal.playlist', array($objs));	//if($type == 2)
				if ($result === NULL)		//if some error occured
				{
					debug("Connection error!!!");
					//set the last try to the current time
					$sql = "UPDATE portal_statistics SET timestamp='".$db->getTimestampTz()."' WHERE name='last_connection_try'";
					$db->query($sql);
				}
			}

			if ($result === NULL)	//if some error occured
			{
				if ($cached === NULL) return array();	//if not in cache and not available

				return unserialize(base64_decode($cached['value']));
			}
			else		//if got result, set last successful connection to current time
			{
				$sql = "UPDATE portal_statistics SET timestamp='".$db->getTimestampTz()."' WHERE name='last_connection'";
				$db->query($sql);

				if ($type == 1) foreach($result as $programme)		//if query, use this results to update the programmes table
				{
					$serial = base64_encode(serialize($programme));
					$sql = "UPDATE portal_cache SET value='$serial', timestamp='".$db->getTimestampTz()."' WHERE type=2 AND name='".$programme['id']."'";
					$db->query($sql);
				}
				else $result = $result[0];

				//save result in cache
				$serial = base64_encode(serialize($result));
				if ($cached === NULL) $sql = "INSERT INTO portal_cache(type, name, value, timestamp) VALUES($type, '$query', '$serial', '".$db->getTimestampTz()."')";
				else $sql = "UPDATE portal_cache SET value='$serial', timestamp='".$db->getTimestampTz()."' WHERE type=$type AND name='$query'";
				$db->query($sql);

				return $result;
			}
		}
		return unserialize(base64_decode($cached['value']));

	}

	function getPlaylists()
	{
		global $db, $page;
		$playlists = array();

		$sql="SELECT name, id FROM portal_prglist WHERE portal_id = '$this->portal_id'";
		$result = $db->getAll($sql);
		$playlists['unsorted'] = $page->getlocalized("unsorted");
		foreach ($result as $query) $playlists[$query['id']] = $query['name'];

		return $playlists;
	}

	function runPlaylist($name)
	{
		global $db;

		if (($name == "unsorted") or ($name == ""))
		{
			$sql="SELECT progid FROM portal_programmes WHERE portal_id = '$this->portal_id' and prglist_id is NULL";
			$list = $db->getCol($sql);
		}
		elseif ($name != "")	//if not empty
		{
			$name = (string)(int)$name;
			$sql="SELECT progid FROM portal_programmes WHERE portal_id = '$this->portal_id' and prglist_id = $name";
			$list = $db->getCol($sql);
		}
		else return array();

		if ($list == NULL) return array();	//if no result

		return $this->getProgrammes($list);
	}

	function getProgrammes($ids)
	{
		$result = array();
		foreach($ids as $id)
		{
			$programme = $this->getCached($id, 2);
			if (count($programme) > 0) $result[] = $programme;
		}
		return $result;
	}

	function createNewPlaylist($name)
	{
		global $db;
		$playlists = array();
		if (($name == "") or (strtolower($name) == "unsorted")) return false;		//if empty

		$sql="SELECT name FROM portal_prglist WHERE portal_id = '$this->portal_id' and name = '$name'";
		$result = $db->getOne($sql);
		if ($result == NULL)	//if not exists
		{
			$sql="INSERT INTO portal_prglist(portal_id, name) values('$this->portal_id', '$name')";
			$result = $db->query($sql);
			return true;
		}
		return false;
	}


	function getPrgProperties($progid)
	{
		global $db;
		$sql="SELECT teaser, text FROM programmes_description WHERE portal_id = '$this->portal_id' AND progid = '$progid'";
		$properties = $db->getRow($sql);

		$sql="SELECT file_location, filename FROM portal_files WHERE portal_id = '$this->portal_id' AND progid = '$progid'";
		$files = $db->getAll($sql);

		$properties['files'] = array();

		if (count($files) > 0) foreach($files as $file)
		{
			$properties['files'][$file['filename']] = $file['file_location'];
		}

		$properties['comments'] = $this->countComments($progid);	//number of comments

		return $properties;
	}

	function setPrgProperties($progid, $text, $teaser)
	{
		global $db;
		$sql="SELECT teaser, text FROM programmes_description WHERE portal_id = '$this->portal_id' AND progid = '$progid'";
		$result = $db->getRow($sql);
		if ($result == NULL) $sql="INSERT INTO programmes_description(portal_id, progid, text, teaser) VALUES('$this->portal_id', '$progid', '$text', '$teaser')";
			else $sql="UPDATE programmes_description SET text='$text', teaser='$teaser' WHERE portal_id='$this->portal_id' AND progid='$progid'";
		$db->query($sql);
	}


	function getFilters()
	{
		return array(	"all" => "all programmes",
				"teaser" => "programmes without teaser",
				"text" => "programmes without text",
				"something" => "programmes without something");
	}

	function getColors()
	{
		return array(	"none" => "Select color",
				"00FFFF" => "aqua",
				"000000" => "black",
				"0000FF" => "blue",
				"A02820" => "brown",
				"80FF00" => "chartreuse",
				"FF00FF" => "fuchsia",
				"808080" => "gray",
				"008000" => "green",
				"00FF00" => "lime",
				"800000" => "maroon",
				"000080" => "navy",
				"808000" => "olive",
				"FFA000" => "orange",
				"800080" => "purple",
				"FF0000" => "red",
				"C0C0C0" => "silver",
				"008080" => "teal",
				"F080F0" => "violet",
				"FFFFFF" => "white",
				"FFFF00" => "yellow");
	}

	function correctColor($color)
	{
		$color = dechex(hexdec($color));
		while (strlen($color) < 6) $color = "0".$color;
		return $color;
	}

	function addProgrammeToList($prg_id, $name)
	{
		global $db;
		if ($name == "unsorted")
		{
			$sql="SELECT id FROM portal_programmes WHERE portal_id = '$this->portal_id' AND progid='$prg_id' AND prglist_id is NULL";
			$result = $db->getOne($sql);
			if ($result == NULL)		//if not exists
			{
				$sql="INSERT INTO portal_programmes(portal_id, progid, prglist_id) values('$this->portal_id', '$prg_id', NULL)";
				$db->query($sql);
			}
			else return false;
		}
		else
		{
			$sql="SELECT id FROM portal_programmes WHERE portal_id = '$this->portal_id' AND progid='$prg_id' AND prglist_id = '$name'";
			$result = $db->getOne($sql);
			if ($result == NULL)		//if not exists
			{
				$sql="INSERT INTO portal_programmes(portal_id, progid, prglist_id) values('$this->portal_id', '$prg_id', '$name')";
				$db->query($sql);
			}
			else return false;
		}
		return true;
	}

	function deleteProgrammeFromList($prg_id, $name)
	{
		global $db;
		if ($name == "unsorted")
		{
			$sql="DELETE FROM portal_programmes WHERE portal_id = '$this->portal_id' AND progid='$prg_id' AND prglist_id is NULL";
			$db->query($sql);
		}
		else
		{
			$sql="DELETE FROM portal_programmes WHERE portal_id = '$this->portal_id' AND progid='$prg_id' AND prglist_id = '$name'";
			$db->query($sql);
		}
	}

	function changePortalPassword($old, $new)		//for changeing the portal (upload) password
	{
		global $db;
		if ($old != $this->portal_password) return true;
		$query = "UPDATE portal_settings SET password='$new' WHERE id='$this->portal_id'";
		$db->query($query);
		return false;
	}

	function uploadData($type, $data, $portal_password)		//for upload programmes and queries from the node
	{
		global $db, $page;
		if ($this->portal_password != $portal_password) return $page->getlocalized("wrong_password");	//if password not right
		if (($data['name'] == "") OR ($data['query'] == "")) return $page->getlocalized("data_missing");	//if something is missing

		if ($type == "query")
		{
			$sql="SELECT query FROM portal_queries WHERE portal_id = '$this->portal_id' AND query='".$data['query']."'";
			$result = $db->getOne($sql);
			if ($result == NULL)		//if not exists
			{
				$sql="SELECT name FROM portal_queries WHERE portal_id = '$this->portal_id' AND name='".$data['name']."'";
				$result = $db->getOne($sql);
				if ($result == NULL)		//if not exists
				{
					$sql="INSERT INTO portal_queries(portal_id, query, name) values('$this->portal_id', '".$data['query']."', '".$data['name']."')";
					$db->query($sql);
				}
				else return $page->getlocalized("name_exists");
			}
			else return $page->getlocalized("query_exists");
		}
		elseif ($type == "prg")
		{
			$r = $this->addProgrammeToList($data, "unsorted");
			if ($r == true) return "OK";
			else return $page->getlocalized("one_not_added");
		}
		elseif ($type == "prglist")
		{
			$d = explode('|', $data);
			$max = count($d);
			$error = 0;
			for ($i=0; $i<$max; $i++)
			{
				if ($d[$i] != "")
				{
					$r = $this->addProgrammeToList($d[$i], "unsorted");
					if ($r != true) $error++;		//could not add
				}
			}
			if ($error == 0) return "OK";
			else return (string)($error)." ".$page->getlocalized("not_added");
		}
		else return $page->getlocalized("bad_type");		//if bad type given

		return "OK";
	}

	function getPortals()
	{
		global $db;

		$sql="SELECT name, id FROM portal_settings WHERE true";
		$portals = $db->getAll($sql);
		return $portals;
	}

	function getTemplates($published = true)	//default is only piblished templates
	{
		global $db;

		if ($published) $sql="SELECT id, name, picture FROM portal_templates WHERE published = true";	//only published
		else $sql="SELECT id, name, picture FROM portal_templates WHERE true";		//all templates
		$portals = $db->getAll($sql);
		return $portals;
	}

	function addComment($progid, $user_id, $reply_to, $title, $comment, $email)
	{
		global $db, $user, $page;

		$IPaddr = $_SERVER['REMOTE_ADDR'];
		$comment = nl2br(htmlentities($comment));
		$title = htmlentities(substr($title, 0, 30));
		$email = htmlentities(substr($email, 0, 100));
		if (($title == "") OR ($comment == "")) return false;		//if not filled out
		$level = 0;
		$path = "0";
		if ($reply_to == "") $reply_to = "NULL";
		else	//check if parent exists and get data
		{
			$sql="SELECT level, path FROM programmes_comments WHERE id = $reply_to";
			$data = $db->getRow($sql);
			if ($data == NULL) return false;
			$level = $data['level']+1;	//the reply must be one level higher
			$path = $data['path'];
		}
		$sql="SELECT path FROM programmes_comments WHERE portal_id=$this->portal_id AND progid = '$progid' AND path LIKE '$path.____' ORDER BY path DESC";
		$c = (int)substr($db->getOne($sql), -4);	//get highest number on this level
		if ($c > 9998) return false;	//can not store more than 9999 subcomments
		$counter = (string)($c+1);		//increase with one
		if (strlen($counter) == 1) $counter = "000".$counter;
		if (strlen($counter) == 2) $counter = "00".$counter;
		if (strlen($counter) == 3) $counter = "0".$counter;

		if ($email == NULL) $sql="INSERT INTO programmes_comments(portal_id, progid, user_id, reply_to, title, comment, level, path, IPaddr) values($this->portal_id, '$progid', $user_id, $reply_to, '$title', '$comment', $level, '$path.$counter', '$IPaddr')";
		else  $sql="INSERT INTO programmes_comments(portal_id, progid, user_id, reply_to, title, comment, level, path, IPaddr, email) values($this->portal_id, '$progid', NULL, $reply_to, '$title', '$comment', $level, '$path.$counter', '$IPaddr', '$email')";

		$this->addEvent("comment", array("prog_id" => $progid,"title" => $title, "comment" => $comment, "user_name" => $user->getName(), "email" => $email, "path" => $path.$counter, "host" => getHostName(), "authkey" => $page->getAuthKey()));

		return $db->query($sql);
	}

	function deleteComment($progid, $user_id, $comment_id)
	{
		global $db;
		$sql="SELECT path FROM programmes_comments WHERE id=$comment_id";
		$path = $db->getOne($sql);		//path of the comment to delete
		if ($path == NULL) return false;
		$sql="DELETE FROM programmes_comments WHERE path LIKE '$path%'";
		return $db->query($sql);
	}

	function getComments($progid)
	{
		global $db, $MAX_COMMENT_DEPTH;

		//$sql="SELECT id, path, portal_users.name, email, timestamp, title, comment, level FROM programmes_comments WHERE portal_id=$this->portal_id AND progid = '$progid' AND user_id=portal_users.id ORDER BY path";
		//$sql="SELECT id, path, email as name, timestamp, title, comment, level FROM programmes_comments WHERE portal_id=$this->portal_id AND progid = '$progid' ORDER BY path";

		$sql="SELECT programmes_comments.id, path, portal_users.name, programmes_comments.email, timestamp, title, comment, level FROM programmes_comments LEFT JOIN portal_users ON programmes_comments.user_id=portal_users.id WHERE programmes_comments.portal_id=$this->portal_id AND progid = '$progid' ORDER BY path";

		$result = $db->getAll($sql);
		$comments = array();
		if ($result == NULL) return $comments;

		$oldlevel = 0;
		foreach ($result as $comment)
		{
			$level = $comment['level'];
			if ($level > $MAX_COMMENT_DEPTH) $level = $MAX_COMMENT_DEPTH;
			if ($level > $oldlevel) while ($level > $oldlevel)
			{
				$oldlevel++;
				$comment['ul'] .= "<ul>";
			}
			else while ($level < $oldlevel)
			{
				$oldlevel--;
				$comment['ul'] .= "</ul>";
			}
			$comments[$comment['id']] = $comment;
			$oldlevel = $level;
		}

		while (0 < $oldlevel)
		{
			$oldlevel--;
			$comment['last'] .= "</ul>";
		}
		$comments[$comment['id']] = $comment;


		return $comments;
	}

	function countComments($progid)
	{
		global $db;

		$sql="SELECT count(*) FROM programmes_comments WHERE programmes_comments.portal_id=$this->portal_id AND progid = '$progid'";

		$result = $db->getOne($sql);
		if ($result == NULL) return 0;
		return $result;
	}

	function addEvent($name, $value, $timestamp = NULL)
	{
		global $db;

		if (($name == 'query') OR ($name == 'programme'))
		{
			$sql="UPDATE portal_statistics SET timestamp2='".$db->getTimestampTz()."' WHERE name='$name' AND portal_id='$this->portal_id' AND value='$value'";
			$db->query($sql);
			if ($db->affectedRows() == 0)	//if it wasn't on the portal yet
			{
				$timestamp = $db->getTimestampTz();		//current timestamp
				$sql="INSERT INTO portal_statistics(name, timestamp, timestamp2, portal_id, value) VALUES('$name', '$timestamp', '$timestamp', '$this->portal_id', '$value')";
				$db->query($sql);
				$this->addEvent($name."_added", $value, $timestamp);
			}
		}
		elseif (($name == 'query_deleted') OR ($name == 'programme_deleted') OR ($name == 'query_added') OR ($name == 'programme_added'))
		{
			$sql="INSERT INTO portal_events(name, timestamp, portal_name, value) VALUES('$name', '$timestamp', '$this->portal_name', '$value')";
			$db->query($sql);
		}
		elseif (($name == 'comment') OR ($name == 'rating'))
		{
			$timestamp = $db->getTimestampTz();		//current timestamp
			$serial = base64_encode(serialize($value));
			$sql="INSERT INTO portal_events(name, timestamp, portal_name, value) VALUES('$name', '$timestamp', '$this->portal_name', '$serial')";
			$db->query($sql);
		}
		elseif ($name == 'visit')
		{
			$timestamp = $db->getTimestampTz();		//current timestamp
			$serial = base64_encode(serialize($value));
			$sql="UPDATE portal_events SET timestamp='$timestamp' WHERE name='$name' AND portal_name = '$this->portal_name' AND value = '$serial'";
			$db->query($sql);
			if ($db->affectedRows() == 0)
			{
				$sql="INSERT INTO portal_events(name, timestamp, portal_name, value) VALUES('$name', '$timestamp', '$this->portal_name', '$serial')";
				$db->query($sql);
			}
		}
		elseif ($name == 'users')
		{
			$timestamp = $db->getTimestampTz();		//current timestamp
			$sql="INSERT INTO portal_events(name, timestamp, portal_name, value) VALUES('$name', '$timestamp', '$this->portal_name', '$value')";
			$db->query($sql);
		}
		elseif ($name == 'portal_updated')
		{
			$timestamp = $db->getTimestampTz();		//current timestamp
			$sql="UPDATE portal_events SET timestamp='$timestamp' WHERE name='$name' AND portal_name = '$this->portal_name' AND value = '$value'";
			$db->query($sql);
			if ($db->affectedRows() == 0)
			{
				$sql="INSERT INTO portal_events(name, timestamp, portal_name, value) VALUES('$name', '$timestamp', '$this->portal_name', '$value')";
				$db->query($sql);
			}
		}
	}

	function searchOldEvents()
	{
		global $db, $MIN_REMOVAL_TIME, $MIN_EVENT_SENDING, $sotfSite;

		$sql="SELECT * FROM portal_statistics WHERE (name='query' OR name='programme') AND portal_id='$this->portal_id'";
		$result = $db->getAll($sql);
		foreach ($result as $event)
		{
			if ($db->diffTimestamp($db->getTimestamp(), $event['timestamp2']) > $MIN_REMOVAL_TIME)
			{
				$sql="DELETE FROM portal_statistics WHERE id=".$event['id'];
				$db->query($sql);
				$this->addEvent($event['name']."_deleted", $event['value'], $event['timestamp2']);
			}
		}

		$sql = "SELECT timestamp FROM portal_statistics WHERE name='events_sent'";
		$last_refresh = $db->diffTimestamp($db->getTimestamp(), $db->getOne($sql));

		if ($last_refresh < $MIN_EVENT_SENDING)
		{
			debug("Sending events...");
			$rpc = new rpc_Utils;
			$url = $sotfSite."xmlrpcServer.php";

			$sql="SELECT id, name, portal_id as portal_name, timestamp, number as value FROM portal_statistics WHERE name='page_impression'";
			$page_impression = $db->getAll($sql);

			$sql="SELECT * FROM portal_events WHERE true";
			$events = $db->getAll($sql);

			$sql="DELETE FROM portal_events WHERE WHERE true";
			//$db->query($sql);

			$events = array_merge($events, $page_impression);
			foreach($events as $key => $event)
			{
				if ($event['name'] == 'page_impression') $events[$key]['portal_name'] = $this->portal_name;
				elseif (($event['name'] == 'comment') OR ($event['name'] == 'rating') OR ($event['name'] == 'visit'))
					$events[$key]['value'] = unserialize(base64_decode(($event['value'])));
			}
			
			$objs = array($events);

			$result = $rpc->call($url, 'portal.events', $objs);
			if ($result === NULL)		//if some error occured
			{
				debug("Connection error!!!");
				//set the last try to the current time
				$sql = "UPDATE portal_statistics SET timestamp='".$db->getTimestampTz()."' WHERE name='last_connection_try'";
				$db->query($sql);
			}
			else
			{
				$sql = "UPDATE portal_statistics SET timestamp='".$db->getTimestampTz()."' WHERE name='events_sent'";
				$db->query($sql);
			}

		}


	}

}


class portal_user
{
	var $id = -1, $name = "", $email = "", $activate = false;
	
	function portal_user($portal_id, $username = NULL, $password = NULL)			//constuctor
	{
		global $db;
		if (!isset($username) OR !isset($password))
		{
			$username = $_SESSION['username'];	//load username and password to session
			$password = $_SESSION['password'];
		}

		$query = "SELECT id, email, name, activate FROM portal_users WHERE name='$username' AND password='$password' AND portal_id=$portal_id";	// AND activate IS NULL
		$result = $db->getRow($query);
		if ($result != NULL)		//if logged in
		{
			if ($result['activate'] != NULL) $this->activate = $result['activate'];
			else
			{
				$this->id = $result['id'];		//set user_id
				$this->name =  $result['name'];		//set name
				$this->email = $result['email'];	//set email
				$_SESSION['username'] = $username;	//save username and password to session
				$_SESSION['password'] = $password;
			}
		}
		//else $this->id = -1;		//-1 if not logged in
	}

	function sendMail($portal_id, $username, $type = "password")
	{
		global $db, $page;
		$sql = "SELECT email, activate, password FROM portal_users WHERE portal_id=$portal_id AND name='$username'";
		$data = $db->getRow($sql);
		if ($email == NULL)		//if not exsist
		{
			$email = $data['email'];
			$activate = $data['activate'];
			$password = $data['password'];
			if ($type == "password")
			{
				mail($email, $page->getlocalized("your_password"), $page->getlocalized("your_password2")." $password");
			}
			if ($type == "activate")
			{
				mail($email, $page->getlocalized("activation_code"), $page->getlocalized("activation_code2")." $activate");
			}
			else return false;
		}
		else return false;
		return true;
	}

	function addNewUser($portal_id, $username, $user_password, $email)
	{
		global $db, $page;
		if (($username =="") OR ($user_password =="") OR ($email == ""));
		$sql = "SELECT id FROM portal_users WHERE portal_id=$portal_id AND name='$username'";
		if ($db->getOne($sql) == NULL)		//if not exsist
		{
			srand();
			$activate = rand(1, 30000);
			$sql="INSERT INTO portal_users (portal_id, name, password, email, activate) VALUES ('$portal_id', '$username', '$user_password', '$email', $activate)";
			$result = $db->query($sql);
			$sql = "SELECT id FROM portal_users WHERE portal_id=$portal_id AND name='$username' AND password='$user_password'";
			$user_id = $db->getOne($sql);
			$this->sendMail($portal_id, $username, "activate");
			return $user_id;
		}
		else return false;
	}

	function activateUser($portal_id, $username, $user_password, $activate)
	{
		global $db;
		$activate = (int)$activate;
		$query = "SELECT id FROM portal_users WHERE name='$username' AND password='$user_password' AND portal_id=$portal_id AND activate=$activate";
		$id = $db->getOne($query);
		if ($id == NULL) return false;	//not activated, bad password or name or act-code

		$query="UPDATE portal_users SET activate=NULL WHERE id=$id";
		return ($db->query($query));
	}

	function logout()
	{
		//set back vars to default
		$this->id = -1;
		$this->name = "";
		$this->email = "";
		//destroy data saved in sesson
		$_SESSION["username"] = "";			//delete username
		$_SESSION["password"] = "";			//delete password
	}

	function getActivated()
	{
		return $this->activate;	//return activated status ot number
	}

	function getId()
	{
		return $this->id;	//return user_id
	}

	function getName()
	{
		return $this->name;	//return username
	}

	function getEmail()
	{
		return $this->email;	//return email address
	}

	function loggedIn()
	{
		if ($this->id == -1) return false;	//if not logged in return false
		return true;				//else return true
	}

	function countUsers($portal_id)
	{
		global $db;
		$sql="SELECT count(*) FROM portal_users WHERE portal_id=$portal_id";
		return $db->getOne($sql);
	}


//	function getPrefs($portal_admin)
//	{
//		if ($portal_admin == $this->id) $user['admin'] = true;
//			else $user['admin'] = false;
//		$user['id'] = $this->id;
//		return $user;
//	}

}


class html
{
	var $allowed_tags, $errors = array(), $warnings = array(), $messages = array();
	
	function html($allowed_tags = NULL)			//constuctor
	{
		if (is_array($allowed_tags)) $this->setAllowedTags($allowed_tags);
		else	//if list not given use this default list of elements (no onclick, onmouseover...)
		{
			$allow['allow_all'] = array('id', 'class', 'title', 'style', 'dir', 'lang', 'xml:lang');
		
			$allow['a'] = array('href', 'hreflang', 'name', 'type', 'charset');
			$allow['br'] = array();
			$allow['img'] = array('src', 'alt', 'align', 'border', 'height', 'hspace', 'longdesc', 'vspace', 'width');
			$allow['hr'] = array('align', 'noshade', 'size', 'width');	//Defines a horizontal rule
		
			$allow['div'] = array('align');
			$allow['p'] = array('align');
			$allow['blockquote'] = array('cite');
		
			//list elements
			$allow['li'] = array('type', 'value');
			$allow['ol'] = array('type', 'start', 'compact');
			$allow['ul'] = array('type', 'compact');
		
			//headers
			$allow['h1'] = array();
			$allow['h2'] = array();
			$allow['h3'] = array();
			$allow['h4'] = array();
			$allow['h5'] = array();
			$allow['h6'] = array();
		
			//phrase elements
			$allow['em'] = array();			//Renders as emphasized text 
			$allow['strong'] = array();		//Renders as strong emphasized text
			$allow['dfn'] = array();		//Defines a definition term
			$allow['code'] = array();		//Defines computer code text
			$allow['samp'] = array();		//Defines sample computer code
			$allow['kbd'] = array();		//Defines keyboard text
			$allow['var'] = array();		//Defines a variable
			$allow['cite'] = array();		//Defines a citation
		
			$allow['sub'] = array();		//subsctipted text
			$allow['sup'] = array();		//superscripted text
		
			//font style elements
			$allow['tt'] = array();			//Renders as teletype or mono spaced text
			$allow['i'] = array();			//Renders as italic text
			$allow['b'] = array();			//Renders as bold text
			$allow['big'] = array();		//Renders as bigger text
			$allow['small'] = array();		//Renders as smaller text
			$allow['s'] = array();			//strikethrough
			$allow['font'] = array('color', 'face', 'size');

			$this->setAllowedTags($allow);
		}
	}

	function setAllowedTags($allowed_tags)
	{
		if (!isset($allowed_tags['allow_all'])) $allowed_tags['allow_all'] = array();
		$this->allowed_tags = $allowed_tags;
	}

	function getErrors()
	{
		$retval['errors'] = $this->errors;
		$retval['warnings'] = $this->warnings;
		$retval['messages'] = $this->messages;
		return $retval;
	}

	function addError($error, $pos)
	{
		$this->errors[$pos] = $error;
		return $this->getErrors();
	}

	function addWarning($warning, $pos)
	{
		$this->warnings[$pos] = $warning;
		return true;
	}

	function addMessage($message, $pos)
	{
		$this->messages[$pos] = $message;
		return true;
	}

	function analyze_tag($original_tag, $filter = false)
	{
		$length = strlen($original_tag);
		if ($original_tag{0} != '<') return $this->addError("Tag must begin with '<'.", 0);
		if ($original_tag{$length-1} != '>') return $this->addError("Tag must end with '>'.", $length);


		//define states for the machine
		$BEGIN = 0; $TAG_NAME = 1; $ATTRIBUTE_NAME = 2; $ATTRIBUTE_NAME_END = 3;
		$ATTRIBUTE_EQ = 4; $SINGLE_QUOTE = 5; $DOUBLE_QUOTE = 6; $NO_QUOTE = 7;
		$ATTRIBUTE_VALUE_END = 8; $EMPTY_ELEMENT = 9; $END = 10; $TAG_NAME_END = 11;
		//set begin state
		$state = $BEGIN;

		//define whitespace characters
		$SPACES = array(' ', '\r', '\n', '\t', chr(13), chr(10));

		//array to store the analyzed tag
		$tag = array();
		$tag['name'] = "";
		$tag['attributes'] = array();
		$tag['empty'] = false;
		$tag['close'] = false;

		$name = "";
		$value = "";
		$char = $original_tag{0};

		for ($pos = 1; $pos <= $length; $pos++)
		{
			$pchar = $char;
			$char = $original_tag{$pos};

			//var_dump($pos);print(":");var_dump($state);print("<br>");
			switch ($state)
			{
			    case $BEGIN:
				if (in_array($char, $SPACES)) $state = $BEGIN;
				elseif (ereg("[a-zA-Z]", $char)) $state = $TAG_NAME;
				elseif ($char == '/') $tag['close'] = true;
				elseif ($char == '>') return $this->addError("No tag name, tag is empty.", $pos);
				else return $this->addError("Illegal character '$char' (".ord($char).").", $pos);
			        break;
			    case $TAG_NAME:
			    	$tag['name'] .= $pchar;		//add pervious character to the name of the attribute
				if (in_array($char, $SPACES)) $state = $TAG_NAME_END;
				elseif (ereg("[a-zA-Z0-9]", $char)) $state = $TAG_NAME;
				elseif ($char == '/') $state = $EMPTY_ELEMENT;
				elseif ($char == '>') $state = $END;
				else return $this->addError("Illegal character '$char' (".ord($char).").", $pos);
			        break;
			    case $TAG_NAME_END:
				if (in_array($char, $SPACES)) $state = $TAG_NAME_END;
				elseif (ereg("[a-zA-Z]", $char)) $state = $ATTRIBUTE_NAME;
				elseif ($char == '/') $state = $EMPTY_ELEMENT;
				elseif ($char == '>') $state = $END;
				else return $this->addError("Illegal character '$char' (".ord($char).").", $pos);
			        break;
			    case $ATTRIBUTE_NAME:
			    	$name .= $pchar;		//add pervious character to the name of the attribute
				if (ereg("[a-zA-Z0-9:\-]", $char)) $state = $ATTRIBUTE_NAME;
				elseif (in_array($char, $SPACES)) $state = $ATTRIBUTE_NAME_END;
				elseif ($char == '=') $state = $ATTRIBUTE_EQ;
				elseif ($char == '/') return $this->addError("No value for the attribute '$name' given.", $pos);
				elseif ($char == '>') return $this->addError("No value for the attribute '$name' given.", $pos);
				else return $this->addError("Illegal character '$char' (".ord($char).")", $pos);
			        break;
			    case $ATTRIBUTE_NAME_END:
				if (in_array($char, $SPACES)) $state = $ATTRIBUTE_NAME_END;
				elseif ($char == '=') $state = $ATTRIBUTE_EQ;
				elseif (ereg("[a-zA-Z]", $char))
				{
					if (array_key_exists($name, $tag['attributes'])) $this->addWarning("Attribute '$name' redefined.", $pos);
					$tag['attributes'][$name] = "'".$name."'";
					$name = "";
					$value = "";

					$state = $ATTRIBUTE_NAME;
					$this->addWarning("No value for the attribute '$name' given", $pos);
				}
				else return $this->addError("Illegal character '$char' (".ord($char).")", $pos);
			        break;
			    case $ATTRIBUTE_EQ:
				if (in_array($char, $SPACES)) $state = $ATTRIBUTE_EQ;
				elseif ($char == "'") $state = $SINGLE_QUOTE;
				elseif ($char == '"') $state = $DOUBLE_QUOTE;
				elseif (ereg("[a-zA-Z0-9]", $char)) {$state = $NO_QUOTE; $this->addWarning("Value of attribute '$name' not in quotes.", $pos);}
				else return $this->addError("Illegal character '$char' (".ord($char).")", $pos);
			        break;
			    case $SINGLE_QUOTE:
			    	$value .= $pchar;		//add pervious character to the value of the attribute
				if ($char == "'")
				{
				    	$value .= $char;		//add currend character to the value of the attribute
					$state = $ATTRIBUTE_VALUE_END;
				}
				else $state = $SINGLE_QUOTE;
			        break;
			    case $DOUBLE_QUOTE:
			    	$value .= $pchar;		//add pervious character to the value of the attribute
				if ($char == '"')
				{
				    	$value .= $char;		//add currend character to the value of the attribute
					$state = $ATTRIBUTE_VALUE_END;
				}
				else $state = $DOUBLE_QUOTE;
			        break;
			    case $NO_QUOTE:
			    	$value .= $pchar;		//add pervious character to the value of the attribute
				if (in_array($char, $SPACES))
				{
					if (strpos($value, '"') === false) $value = '"'.$value.'"';
					elseif (strpos($value, "'") === false) $value = "'".$value."'";
					else $value = '"'.htmlspecialchars($value).'"';
					$state = $ATTRIBUTE_VALUE_END;
				}
				elseif ($char == '/')
				{
					if (strpos($value, "'") === false) $value = "'".$value."'";
					elseif (strpos($value, '"') === false) $value = '"'.$value.'"';
					else $value = '"'.htmlspecialchars($value).'"';
					if (array_key_exists($name, $tag['attributes'])) $this->addWarning("Attribute '$name' redefined.", $pos);
					$tag['attributes'][$name] = $value;
					$name = "";
					$value = "";
					$state = $EMPTY_ELEMENT;
				}
				elseif ($char == '>')
				{
					if (strpos($value, "'") === false) $value = "'".$value."'";
					elseif (strpos($value, '"') === false) $value = '"'.$value.'"';
					else $value = '"'.htmlspecialchars($value).'"';
					if (array_key_exists($name, $tag['attributes'])) $this->addWarning("Attribute '$name' redefined.", $pos);
					$tag['attributes'][$name] = $value;
					$name = "";
					$value = "";
					$state = $END;
				}
				else $state = $NO_QUOTE;
			        break;
			    case $ATTRIBUTE_VALUE_END:
				if ($name != '')
				{
					if (array_key_exists($name, $tag['attributes'])) $this->addWarning("Attribute '$name' redefined.", $pos);
					$tag['attributes'][$name] = $value;
					$name = "";
					$value = "";
				}
				if (in_array($char, $SPACES)) $state = $ATTRIBUTE_VALUE_END;
				elseif ($char == '/') $state = $EMPTY_ELEMENT;
				elseif ($char == '>') $state = $END;
				elseif (ereg("[a-zA-Z]", $char)) $state = $ATTRIBUTE_NAME;
				else return $this->addError("Illegal character '$char' (".ord($char).")", $pos);
			        break;
			    case $EMPTY_ELEMENT:
				$tag['empty'] = true;
				if ($char == '>') $state = $END;
				elseif (in_array($char, $SPACES)) {$state = $EMPTY_ELEMENT; $this->addWarning("There should be no space between the '/' and '>' signs.", $pos);}
				else return $this->addError("Illegal character '$char' (".ord($char).")", $pos);
			        break;
			    case $END:
			    	$filtered_tag = "";
				if ($filter)
				{
					if (!array_key_exists($tag['name'], $this->allowed_tags)) $this->addMessage("Tag '".$tag['name']."' not allowed.", $tag['name']);
					else
					{
						$filtered_tag = "<";
						if ($tag['close']) $filtered_tag .= "/";
						$filtered_tag .= $tag['name'];
						foreach ($tag['attributes'] as $name => $value)
						{
							if (!in_array($name, $this->allowed_tags[$tag['name']]) AND !in_array($name, $this->allowed_tags['allow_all']))
							{
								$this->addMessage("Attribute '$name' not allowed.", $name);
							}
							else $filtered_tag .= " ".$name."=".$value;
						}
						if ($tag['empty']) $filtered_tag .= " /";
						$filtered_tag .= ">";
					}
				}
				else
				{
					$filtered_tag = "<";
					if ($tag['close']) $filtered_tag .= "/";
					$filtered_tag .= $tag['name'];
					foreach ($tag['attributes'] as $name => $value) $filtered_tag .= " ".$name."=".$value;
					if ($tag['empty']) $filtered_tag .= " /";
					$filtered_tag .= ">";
				}
				$retval = $this->getErrors();
				$retval['filtered'] = $filtered_tag;
				return $retval;
			        break;
			}
		}
	}


	function analyze_text($text, $delete = false)
	{
		$new_text = "";
		$length = strlen($text);
		$tag_begin = -1;
		$tag_end = -1;
		$lastpos = 0;
	
		for ($pos = 0; $pos < $length; $pos++)
		{
			$char = $text{$pos};
			if ($char == '<') $tag_begin = $pos;
			if (($tag_begin != -1) AND ($char == '>'))
			{
				$tag_end = $pos;
				$tag = substr($text, $tag_begin, $tag_end-$tag_begin+1);
				$retval = $this->analyze_tag($tag, true);
				if (count($retval['errors'] == 0) AND count($retval['messages'] == 0) AND $retval['filtered'] != "")		//no fatal errors and no illegal tags
				{
					$new_text .= htmlspecialchars(substr($text, $lastpos, $tag_begin-$lastpos));
					$new_text .= $retval['filtered'];
					$lastpos = $pos+1;
				}
				elseif ($delete)	//if delete true do not include incorect tag
				{
					$new_text .= htmlspecialchars(substr($text, $lastpos, $tag_begin-$lastpos));
					$lastpos = $pos+1;
				}
				$tag_begin = -1;
				$tag_end = -1;
			}
		}
		$new_text .= htmlspecialchars(substr($text, $lastpos, $pos-$lastpos));
		return $new_text;
	}
}

?>