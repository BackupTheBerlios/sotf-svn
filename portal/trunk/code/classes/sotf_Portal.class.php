<?php
/*
 * This class represents a portal outlook
 *
 * @author Mate Pataki MTA SZTAKI DSD
 *
 */

/*
CREATE TABLE "portal_settings" (
"id" SERIAL PRIMARY KEY, 
"name" varchar NOT NULL, 
"template_id" int4 REFERENCES "portal_templates"("id"), 
"admin_id" int4 REFERENCES "portal_users"("id") NOT NULL, 
"settings" varchar , 
"password" varchar NOT NULL );

INSERT INTO "portal_settings" ("name", "template_id", "password") VALUES ('admin', 1, 'admin')  

CREATE TABLE "portal_templates" (
   "id" SERIAL PRIMARY KEY,
   "name" varchar NOT NULL,
   "settings" varchar,
   "published" bool NOT NULL
);

INSERT INTO "portal_templates" ("id", "name", "settings", "published") VALUES(1, 'Under construction', 'YTo3OntzOjU6InRhYmxlIjthOjQ6e2k6MDthOjM6e2k6MDthOjEwOntzOjg6InJlc291cmNlIjtzOjQ6InRleHQiO3M6NToidmFsdWUiO3M6MDoiIjtzOjQ6ImxpbmsiO3M6NDoibm9uZSI7czo1OiJzdHlsZSI7czo0OiJub25lIjtzOjU6ImNsYXNzIjtzOjQ6Im5vbmUiO3M6NToiYWxpZ24iO3M6NjoiY2VudGVyIjtzOjY6InZhbGlnbiI7czo2OiJtaWRkbGUiO3M6NToid2lkdGgiO3M6MDoiIjtzOjU6ImNvbG9yIjtzOjY6ImZmMDAwMCI7czo0OiJodG1sIjtzOjA6IiI7fWk6MTthOjEwOntzOjg6InJlc291cmNlIjtzOjQ6InRleHQiO3M6NToidmFsdWUiO3M6MDoiIjtzOjQ6ImxpbmsiO3M6NDoibm9uZSI7czo1OiJzdHlsZSI7czo0OiJub25lIjtzOjU6ImNsYXNzIjtzOjQ6Im5vbmUiO3M6NToiYWxpZ24iO3M6NjoiY2VudGVyIjtzOjY6InZhbGlnbiI7czo2OiJtaWRkbGUiO3M6NToid2lkdGgiO3M6MDoiIjtzOjU6ImNvbG9yIjtzOjY6ImZmZmZmZiI7czo0OiJodG1sIjtzOjA6IiI7fWk6MjthOjEwOntzOjg6InJlc291cmNlIjtzOjQ6InRleHQiO3M6NToidmFsdWUiO3M6MDoiIjtzOjQ6ImxpbmsiO3M6NDoibm9uZSI7czo1OiJzdHlsZSI7czo0OiJub25lIjtzOjU6ImNsYXNzIjtzOjQ6Im5vbmUiO3M6NToiYWxpZ24iO3M6NjoiY2VudGVyIjtzOjY6InZhbGlnbiI7czo2OiJtaWRkbGUiO3M6NToid2lkdGgiO3M6MDoiIjtzOjU6ImNvbG9yIjtzOjY6IjAwODAwMCI7czo0OiJodG1sIjtzOjA6IiI7fX1pOjE7YToxOntpOjA7YToxMDp7czo4OiJyZXNvdXJjZSI7czo0OiJ0ZXh0IjtzOjU6InZhbHVlIjtzOjA6IiI7czo0OiJsaW5rIjtzOjQ6Im5vbmUiO3M6NToic3R5bGUiO3M6NDoibm9uZSI7czo1OiJjbGFzcyI7czo0OiJub25lIjtzOjU6ImFsaWduIjtzOjY6ImNlbnRlciI7czo2OiJ2YWxpZ24iO3M6NjoibWlkZGxlIjtzOjU6IndpZHRoIjtzOjA6IiI7czo1OiJjb2xvciI7czowOiIiO3M6NDoiaHRtbCI7czowOiIiO319aToyO2E6MTp7aTowO2E6MTA6e3M6ODoicmVzb3VyY2UiO3M6NDoidGV4dCI7czo1OiJ2YWx1ZSI7czozNToiVW5kZXIgY29uc3RydWN0aW9uITxicj48aHIgbm9zaGFkZT4iO3M6NDoibGluayI7czo0OiJub25lIjtzOjU6InN0eWxlIjtzOjQwOiJzaXplOjV8ZmFjZTpBcmlhbCwgSGVsdmV0aWNhLCBzYW5zLXNlcmlmIjtzOjU6ImNsYXNzIjtzOjQ6Im5vbmUiO3M6NToiYWxpZ24iO3M6NjoiY2VudGVyIjtzOjY6InZhbGlnbiI7czo2OiJtaWRkbGUiO3M6NToid2lkdGgiO3M6NDoiMTAwJSI7czo1OiJjb2xvciI7czowOiIiO3M6NDoiaHRtbCI7czo5MzoiPGZvbnQgZmFjZT0iQXJpYWwsIEhlbHZldGljYSwgc2Fucy1zZXJpZiIgc2l6ZT0iNSI+VW5kZXIgY29uc3RydWN0aW9uITxicj48aHIgbm9zaGFkZT48L2ZvbnQ+Ijt9fWk6MzthOjE6e2k6MDthOjEwOntzOjg6InJlc291cmNlIjtzOjU6InNwYWNlIjtzOjU6InZhbHVlIjtzOjA6IiI7czo0OiJsaW5rIjtzOjQ6Im5vbmUiO3M6NToic3R5bGUiO3M6NDoibm9uZSI7czo1OiJjbGFzcyI7czo0OiJub25lIjtzOjU6ImFsaWduIjtzOjY6ImNlbnRlciI7czo2OiJ2YWxpZ24iO3M6NjoibWlkZGxlIjtzOjU6IndpZHRoIjtzOjA6IiI7czo1OiJjb2xvciI7czowOiIiO3M6NDoiaHRtbCI7czowOiIiO319fXM6NjoicG9ydGFsIjthOjQ6e3M6MzoiYmcxIjtzOjY6Ijk5ZmY5OSI7czozOiJiZzIiO3M6NjoiNjZjYzAwIjtzOjQ6ImZvbnQiO3M6NjoiMDAwMDAwIjtzOjM6ImNzcyI7Tjt9czo0OiJob21lIjthOjc6e3M6MjoiYmciO3M6NjoiOTlmZjk5IjtzOjQ6IndhbGwiO3M6NDM6Imh0dHA6Ly93d3cuZHNkLnN6dGFraS5odS9+bWF0ZS9wbTIwNS9iZy5qcGciO3M6NDoiZm9udCI7czo2OiIwMDAwMDAiO3M6NDoibGluayI7czo2OiIwMDMzMDAiO3M6NToiYWxpbmsiO3M6NjoiMDA2NjAwIjtzOjU6InZsaW5rIjtzOjY6IjAwNjYwMCI7czozOiJjc3MiO047fXM6MTA6InByb2dyYW1tZXMiO2E6Nzp7czoyOiJiZyI7czo2OiI5OWZmOTkiO3M6NDoid2FsbCI7czo0MzoiaHR0cDovL3d3dy5kc2Quc3p0YWtpLmh1L35tYXRlL3BtMjA1L2JnLmpwZyI7czo0OiJmb250IjtzOjY6IjAwMDAwMCI7czo0OiJsaW5rIjtzOjY6IjAwMzMwMCI7czo1OiJhbGluayI7czo2OiIwMDY2MDAiO3M6NToidmxpbmsiO3M6NjoiMDA2NjAwIjtzOjM6ImNzcyI7Tjt9czozOiJjc3MiO2I6MDtzOjY6InJhdGluZyI7YjowO3M6NDoiY2hhdCI7YjowO30=', 'f');


CREATE TABLE "portal_users" (
   "id" SERIAL PRIMARY KEY,
   "portal_id" int4 REFERENCES "portal_settings"("id") NOT NULL,
   "name" varchar NOT NULL,
   "password" varchar NOT NULL,
   "email" varchar,
   "activate" int4
);

CREATE TABLE "portal_prglist" (
"id" SERIAL PRIMARY KEY, 
"portal_id" int4 REFERENCES "portal_settings"("id") NOT NULL, 
"name" varchar);

CREATE TABLE "portal_programmes" (
"id" SERIAL PRIMARY KEY,
"portal_id" int4 REFERENCES "portal_settings"("id") NOT NULL,
"progid" varchar (20) NOT NULL,
"prglist_id" int4 REFERENCES "portal_prglist"("id"));

CREATE TABLE "programmes_description" (
"id" SERIAL PRIMARY KEY,
"portal_id" int4 REFERENCES "portal_settings"("id") NOT NULL,
"progid" varchar (20) NOT NULL,
"teaser" varchar,
"text" varchar);


CREATE TABLE "portal_queries" (
"id" SERIAL PRIMARY KEY, 
"portal_id" int4 REFERENCES "portal_settings"("id") NOT NULL, 
"name" varchar NOT NULL,
"query" varchar);

CREATE TABLE "portal_files" (
"id" SERIAL PRIMARY KEY,
"portal_id" int4 REFERENCES "portal_settings"("id") NOT NULL,
"progid" varchar (20),
"file_location" varchar NOT NULL,
"filename" varchar);

CREATE TABLE programmes_comments (
"id" SERIAL PRIMARY KEY,
"portal_id" int4 REFERENCES portal_settings(id) NOT NULL,
"progid" varchar (20),
"user_id" int4 REFERENCES portal_users(id) NOT NULL,
"reply_to" int4 REFERENCES programmes_comments(id),
"path" varchar,
"timestamp" datetime DEFAULT date('now'::datetime) NOT NULL,
"title" varchar,
"comment" varchar,
"level" int2);

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
			if ($cell["link"] != "none") $html .= "<a href='".$cell["link"]."'>";
			$html .= "<img src=\"".$cell["value"]."\" border=\"0\"";
			if ($cell["class"] != "none") $html .= " class=\"".$cell["class"]."\"";
			$html .= ">";
			if ($cell["link"] != "none") $html .= "</a>";
		}
		elseif ($cell["resource"] == "text")
		{
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
		}
		elseif (($cell["resource"] == "query") OR ($cell["resource"] == "playlist"))
		{
			global $IMAGEDIR, $db, $page;

			if ($cell["resource"] == "query") $results = $this->runQuery($cell["value"]);
			else  $results = $this->runPlaylist($cell["value"]);
			if (!(count($results) > 0)) $results = array();		//if no results create empty array();

			$selected_result = array();

			$fields = $this->getAllFieldnames();


			foreach($results as $result)
			{
				$this->programmes_on_portal[$result['id']] = $result;		//collecting for programmes editor page
				$prgprop = $this->getPrgProperties($result['id']);
				$item['teaser'] = $prgprop['teaser'];
				$item['text'] = $prgprop['text'];
				$item['files'] = $prgprop['files'];

				foreach($result as $key => $value)
					if (array_key_exists($key, $fields) AND $key != 'title')		//title is presented on a diferent level
						if ($key == 'language' AND $value != "") $values[$fields[$key]] = $page->getlocalized($value);	//language need to be translated
						else $values[$fields[$key]] = $value;
				$item['title'] = $result['title'];
				$item['id'] = $result['id'];
				$item['icon'] = $result['icon'];
				$item['values'] = $values;
				$selected_result[] = $item;
				$item = "";
				$values = "";
			}
			$html .= "<table>";
			foreach($selected_result as $item)
			{
				$html .= "<tr><td>";
				if ($item[icon])
				{
					$html .= "<img src=\"".$item['icon']."\"";
					if ($cell["class"] != "none") $html .= " class=\"".$cell["class"]."icon\"";
					$html .= ">";
				}
				else
				{
					$html .= "<img src=\"$IMAGEDIR/noicon.png\"";
					if ($cell["class"] != "none") $html .= " class=\"".$cell["class"]."icon\"";
					$html .= ">";
				}
				$html .= "</td><td><b><a href=\"$php_self?id=$item[id]\">";
				if ($cell["class"] != "none") $html .= "<span class=\"".$cell["class"]."title\">";
				$html .= $item["title"];
				if ($cell["class"] != "none") $html .= "</span>";
				$html .= "</a></b><BR>";
				foreach($item["values"] as $name => $value)
					if ($value != "")
					{
						if ($cell["class"] != "none") $html .= "<span class=\"".$cell["class"]."name\">";
						$html .= "$name: ";
						if ($cell["class"] != "none") $html .= "</span><span class=\"".$cell["class"]."value\">";
						$html .= "$value<br>";
						if ($cell["class"] != "none") $html .= "</span>";
					}
				if (count($item['files']) > 0)
				{
					$html .= "<b>".$page->getlocalized("uploaded_files")."</b>:";
					foreach ($item['files'] as $filename => $file_location)
						$html .= "&nbsp;&nbsp;<a href=\"$file_location\">$filename</a>,";
					$html = substr($html, 0, -1);		//delete last ,
				}
				if ($item['teaser']) $html .= "<br>".$item['teaser']."<br>";
				$html .= "<small>&nbsp;<br></small><b>LISTEN</b> 24MP3 | 64OGG | 0 COMMENTS | RATING<br><br>";
				$html .= "</td></tr>";
			}
			$html .= "</table>";
		}
		
		$this->settings['table'][$row][$col][html] = $html;
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
		global $sotfSite;
		$rpc = new rpc_Utils;
		$url = $sotfSite."xmlrpcServer.php";
		$objs = array($query);
		return $rpc->call($url, 'portal.query', $objs);
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

		if ($name == "unsorted")
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
//		$rpc = new rpc_Utils;			//load xmlrpc
//		$url = $sotfSite."xmlrpcServer.php";
//		$objs = array($list);
//
//		return $rpc->call($url, 'portal.playlist', $objs);	//return the result
	}

	function getProgrammes($ids)
	{
		global $sotfSite;

		$rpc = new rpc_Utils;			//load xmlrpc
		$url = $sotfSite."xmlrpcServer.php";
		$objs = array($ids);

		return $rpc->call($url, 'portal.playlist', $objs);	//return the result
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

	function uploadData($type, $data, $portal_password)		//for upload programmes and queries from the node
	{
		global $db;
		if ($this->portal_password != $portal_password) return false;	//if password not right
		if ($type == "query")
		{
			$sql="SELECT name FROM portal_queries WHERE portal_id = '$this->portal_id' AND name='".$data['name']."'";
			$result = $db->getOne($sql);
			if ($result == NULL)		//if not exists
			{
				if (($data['name'] == "") OR ($data['query'] == "")) return false;	//if something is missing
				$sql="INSERT INTO portal_queries(portal_id, query, name) values('$this->portal_id', '".$data['query']."', '".$data['name']."')";
				$db->query($sql);
			}
			else return false;
		}
		elseif ($type == "prg")
		{
			$sql="SELECT id FROM portal_programmes WHERE portal_id = '$this->portal_id' AND progid='$data' AND prglist_id is NULL";
			$result = $db->getOne($sql);
			return $this->addProgrammeToList($data, "unsorted");
			//if ($result == NULL)		//if not exists
			//{
			//	$sql="INSERT INTO portal_programmes(portal_id, progid, prglist_id) values('$this->portal_id', '$data', NULL)";
			//	$db->query($sql);
			//}
			//else return false;
		}
		elseif ($type == "prglist")
		{
		}
		else return false;		//if bad type given

		return true;
	}

	function getPortals()
	{
		global $db;

		$sql="SELECT name, id FROM portal_settings WHERE true";
		$portals = $db->getAll($sql);
		return $portals;
	}

	function addComment($progid, $user_id, $reply_to, $title, $comment)
	{
		global $db;
		$comment = nl2br(htmlentities($comment));
		$title = htmlentities(substr($title, 0, 30));
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

		$sql="INSERT INTO programmes_comments(portal_id, progid, user_id, reply_to, title, comment, level, path) values($this->portal_id, '$progid', $user_id, $reply_to, '$title', '$comment', $level, '$path.$counter')";
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
		$sql="SELECT id, path, portal_users.name, timestamp, title, comment, level FROM programmes_comments WHERE portal_id=$this->portal_id AND progid = '$progid' AND user_id=portal_users.id ORDER BY path";
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

	function loggedIn()
	{
		if ($this->id == -1) return false;	//if not logged in return false
		return true;				//else return true
	}

//	function getPrefs($portal_admin)
//	{
//		if ($portal_admin == $this->id) $user['admin'] = true;
//			else $user['admin'] = false;
//		$user['id'] = $this->id;
//		return $user;
//	}

}
?>