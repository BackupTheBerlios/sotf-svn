<?php

require("portal_login.php");

if (sotf_Utils::getParameter('logout'))			//if logout link pressed
{
	$user->logout();				//logout user
	$page->redirect($_SERVER["PHP_SELF"]);		//redirect page
}

////get parameter which mode is active
$login = sotf_Utils::getParameter('login');		//login page
if ($portal->isAdmin($user->getId()))		//only for admin users
{
	$playlist = sotf_Utils::getParameter('playlist');	//programme editor mode
	$style = sotf_Utils::getParameter('style');		//style editor mode
	$edit = sotf_Utils::getParameter('edit');		//edit mode
	$id = sotf_Utils::getParameter('id');			//programme view mode (programmes page)
	$admin = sotf_Utils::getParameter('admin');		//admin page

	////settings for the portal, table and others
	$settings = $_SESSION["settings"];			//load current settings from session
	if ($settings["table"] == "") $settings = $portal->loadSettings();	//if not found load saved portal
		else $portal->setSettings($settings);				//if found init portal object with it
}
else $settings = $portal->loadSettings();	//if not admin load saved portal settings

if ($portal->isAdmin($user->getId()))		//only for admin users
{
	if (sotf_Utils::getParameter('save_style'))		//in edit style mode SAVE button pressed
	{
		//Portal menu
		$settings["portal"]["bg1"] = $portal->correctColor(sotf_Utils::getParameter('portal_bg1'));
		$settings["portal"]["bg2"] = $portal->correctColor(sotf_Utils::getParameter('portal_bg2'));
		$settings["portal"]["font"] = $portal->correctColor(sotf_Utils::getParameter('portal_font'));
		$settings["portal"]["css"] = sotf_Utils::getParameter('portal_css');
	
		//Upload file (picture or CSS)
		sotf_Utils::getParameter('file_name');
		sotf_Utils::getParameter('file_file');
		sotf_Utils::getParameter('file_upload');
	
		//Portal home
		$settings["home"]["bg"] = $portal->correctColor(sotf_Utils::getParameter('home_bg'));
		$settings["home"]["font"] = $portal->correctColor(sotf_Utils::getParameter('home_font'));
		$settings["home"]["link"] = $portal->correctColor(sotf_Utils::getParameter('home_link'));
		$settings["home"]["alink"] = $portal->correctColor(sotf_Utils::getParameter('home_alink'));
		$settings["home"]["vlink"] = $portal->correctColor(sotf_Utils::getParameter('home_vlink'));
		$settings["home"]["wall"] = sotf_Utils::getParameter('home_wall');
		$settings["home"]["css"] = sotf_Utils::getParameter('home_css');
	
		//Programmes page
		$settings["programmes"]["bg"] = $portal->correctColor(sotf_Utils::getParameter('programmes_bg'));
		$settings["programmes"]["font"] = $portal->correctColor(sotf_Utils::getParameter('programmes_font'));
		$settings["programmes"]["link"] = $portal->correctColor(sotf_Utils::getParameter('programmes_link'));
		$settings["programmes"]["alink"] = $portal->correctColor(sotf_Utils::getParameter('programmes_alink'));
		$settings["programmes"]["vlink"] = $portal->correctColor(sotf_Utils::getParameter('programmes_vlink'));
		$settings["programmes"]["wall"] = sotf_Utils::getParameter('programmes_wall');
		$settings["programmes"]["css"] = sotf_Utils::getParameter('programmes_css');
	
		//goto after save
		if (sotf_Utils::getParameter('goto') == "programmes") $playlist = true;
		elseif (sotf_Utils::getParameter('goto') == "edit") $edit = true;
		elseif (sotf_Utils::getParameter('goto') == "view") $view = true;
		elseif (sotf_Utils::getParameter('goto') == "admin") $admin = true;
	}
	elseif (sotf_Utils::getParameter('save_changes'))		//admin page save button pressed
	{
	
		if (sotf_Utils::getParameter('stylesheet')) $settings["css"] = true;
			else $settings["css"] = false;
		if (sotf_Utils::getParameter('rating')) $settings["rating"] = true;
			else $settings["rating"] = false;
		if (sotf_Utils::getParameter('chat')) $settings["chat"] = true;
			else $settings["chat"] = false;
	
		$portal->saveSettings($settings);
	}
	elseif (sotf_Utils::getParameter('insert_row_x'))		//insert row button pressed
	{
		//var_dump((substr(sotf_Utils::getParameter('edit'),1)));
		$portal->insertRow((substr(sotf_Utils::getParameter('edit'),1)));
	}
	elseif (sotf_Utils::getParameter('delete_row_x'))		//insert row button pressed
	{
		//var_dump((substr(sotf_Utils::getParameter('edit'),1)));
		$portal->deleteRow((substr(sotf_Utils::getParameter('edit'),1)));
	}

	if ($playlist)						//on programmes editor page
	{
		$filter = sotf_Utils::getParameter('filter');		//filter dropdown box
		$prglist = sotf_Utils::getParameter('prglist');		//pgogrammes list dropdown box
	
		if ($prglist == "queries" OR $prglist == "playlists") $prglist = "current";
	
		if ($prglist{0} == "q")			//first char indicates wther its a query or statik prg list
		{
			//$advsearch = new sotf_AdvSearch();			//create new search object object
			//$SQLquery = $advsearch->Deserialize(substr($prglist, 1));	//deserialize the content (cut first char)
			//$query = $advsearch->GetSQLCommand();
			//$results = $db->getAll($query);
			$results = $portal->runQuery($query);
		}
		elseif ($prglist{0} == "p")
		{
			$results=array();
		}
		else	//current programmes
		{
			$results=array();
		}
	
		$fields[title] = $page->getlocalized("title");
		$fields[alternative_title] = $page->getlocalized("alternative_title");
		$fields[episode_title] = $page->getlocalized("episode_title");
		$fields[seriestitle] = $page->getlocalized("seriestitle");
		$fields[broadcast_date] = $page->getlocalized("broadcast_date");
		$fields[station] = $page->getlocalized("station");
		$fields[language] = $page->getlocalized("language");
		$fields[length] = $page->getlocalized("length");
		$selected_result = array();
	
		foreach($results as $result)
		{
			foreach($result as $key => $value)
				if (array_key_exists($key, $fields) AND $key != 'title')		//title is presented on a diferent level
					if ($key == 'language' AND $value != "") $values[$fields[$key]] = $page->getlocalized($value);	//language need to be translated
					else $values[$fields[$key]] = $value;
			$item[title] = $result['title'];
			$item[id] = $result[id];
var_dump($result['icon']);
			$item['icon'] = $result['icon'];
			$item[values] = $values;
			$selected_result[] = $item;
			$item = "";
			$values = "";
		}
	
		$smarty->assign("result", $selected_result);
	
		foreach ($portal->getQueries() as $key => $value)
			$queries["q".$key] = "&nbsp;-&nbsp;&nbsp;".$value;
		foreach ($portal->getPlaylists() as $key => $value)
			$playlists["p".$key] = "&nbsp;-&nbsp;&nbsp;".$value;
	
		$prglists = array_merge(
				array("current" => "&nbsp;-&nbsp;&nbsp;".$page->getlocalized("prg_on_portal"),
					"queries" => $page->getlocalized("queries").":"),
				$queries,
				array("playlists" => $page->getlocalized("static_lists").":"),
				$playlists);
	
		$smarty->assign("prglists", $prglists);
		$smarty->assign("prglist", $prglist);
	
	//	<option value="current">Programmes on the portal</option>
	//	<option value="none">--{#queries#}--</option>
	//	{html_options options=$queries}
	//	<option value="none">--{#static_lists#}--</option>
	//	{html_options options=$playlists}
	//	$smarty->assign("queries", $portal->getQueries());
	//	$smarty->assign("playlists", $portal->getPlaylists());
	
		$smarty->assign("filters", $portal->getFilters());
		$smarty->assign("filter", $filter);
	}
	elseif ($id)	//if programmes view
	{
	  $smarty->assign('ID', $id);
	
	  $prg = & new sotf_Programme($id);
	
	  $page->setTitle($prg->get('title'));
	
	  // general data
	  $smarty->assign('PRG_DATA', $prg->getAllWithIcon());
	  // station data
	  $station = $prg->getStation();
	  $smarty->assign('STATION_DATA', $station->getAllWithIcon());
	  // series data
	  $series = $prg->getSeries();
	  if($series) {
	    $smarty->assign('SERIES_DATA', $series->getAllWithIcon());
	  }
	
	  // roles and contacts
	  $smarty->assign('ROLES', $prg->getRoles());
	  // genre
	
	  // topics
	  $smarty->assign('TOPICS', $prg->getTopics());
	
	  $smarty->assign('GENRE', $repository->getGenreName($prg->get('genre_id')));
	  // language
	  $smarty->assign('LANGUAGE', $page->getlocalized($prg->get('language')));
	  // rights sections
	  $smarty->assign('RIGHTS', $prg->getAssociatedObjects('sotf_rights', 'start_time'));
	
	  // audio files 
	  $audioFiles = $prg->getAssociatedObjects('sotf_media_files', 'main_content DESC, filename');
	  for($i=0; $i<count($audioFiles); $i++) {
	    $audioFiles[$i] =  array_merge($audioFiles[$i], sotf_AudioFile::decodeFormatFilename($audioFiles[$i]['format']));
	  }
	  $smarty->assign('AUDIO_FILES', $audioFiles);
	
	  // other files
	  $otherFiles = $prg->getAssociatedObjects('sotf_other_files', 'filename');
	  $smarty->assign('OTHER_FILES', $otherFiles);
	  
	  // links
	  $smarty->assign('LINKS', $prg->getAssociatedObjects('sotf_links', 'caption'));
	
	  // referencing portals
	  $smarty->assign('REFS', $prg->getRefs());
	
	  // statistics
	  $smarty->assign('STATS', $prg->getStats());
	
	  // add this visit to statistics
	  $prg->addStat('', "visits");
	
	  // rating
	  $rating = new sotf_Rating();
	  $smarty->assign('RATING', $rating->getInstantRating($id));
	}

	////save cuttent portal table to the session
	$settings["table"] = $portal->getTable();		//save current table
	$_SESSION["settings"] = $settings;			//save current settings

}	//end of admin section

////SMARTY variables
$smarty->assign("table", $portal->getTable());		//current layout table

//menu clicked
if (!$login AND !$playlist AND !$style AND !$edit AND !$id AND !$admin) $view = true;		//set default to view mode
$smarty->assign("login", $login);	//login page
$smarty->assign("playlist", $playlist);	//in editstyle mode
$smarty->assign("style", $style);	//in editstyle mode
$smarty->assign("edit", $edit);		//in edit mode
$smarty->assign("view", $view);		//in view result mode
$smarty->assign("id", $id);		//in view programme mode
$smarty->assign("admin", $admin);	//admin page

//user rights and options
$smarty->assign("is_admin", $portal->isAdmin($user->getId()));		//true if admin
$smarty->assign("is_logged_in", $user->loggedIn());			//true if logged in
$smarty->assign("username", $user->getName());				//username (if logged in)

//directories and names
$smarty->assign("rootdir", $rootdir);				//root directory (portal/www)
$smarty->assign("php_self", $_SERVER['PHP_SELF']);		//php self for the form submit and hrefs
$smarty->assign("portal_name", $portal_name);			//name of the portal
$smarty->assign("sotfSite", $sotfSite);				//location of the StreamontheFly portal

$smarty->assign("portal", $settings["portal"]);
$smarty->assign("home", $settings["home"]);
$smarty->assign("programmes", $settings["programmes"]);
$smarty->assign("css", $settings["css"]);			//CSS enabled
$smarty->assign("chat", $settings["chat"]);			//chat enabled
$smarty->assign("rating", $settings["rating"]);			//rating enabled

$smarty->assign("colors", $portal->getColors());
$smarty->assign("files", $portal->getUploadedFiles());

//$smarty->assign("settings", $settings);		//*********************DEBUG
//$smarty->assign("numbers", $portal->getNumbers());		//OLD finction
//$smarty->assign("rowlength", $portal->getRowLength());	//OLD function

$page->send("portal.htm");
//$page->send();

?>
