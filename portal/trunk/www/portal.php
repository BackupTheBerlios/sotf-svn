<?php

//$anhour = gmdate('D, d M Y H:i:s T', gmmktime(gmdate("H"),gmdate("i")+60,0,gmdate("m"),(gmdate("d")),gmdate("Y")));
//header("Expires: $anhour");

require("portal_login.php");
$error = "";			//to collect the (error) messages for the user

if (sotf_Utils::getParameter('logout'))			//if logout link pressed
{
	$user->logout();				//logout user
  debug("user logged out");
	$page->redirect($_SERVER["PHP_SELF"]);		//redirect page
}



$prglist = sotf_Utils::getParameter('prglist');
$filter = sotf_Utils::getParameter('filter');

//prevent browsers from reload/reprocess the page, save the variables to the session that are needed after reload
if ( (count($_POST) > 0) OR (isset($prglist) AND isset($filter)) )
{
	if (isset($filter)) $_SESSION['filter'] = $filter;		//filter dropdown box on programmes editor page
	if (isset($prglist)) $_SESSION['prglist'] = $prglist;		//pgogrammes list dropdown box on programmes editor page
}


////get parameter which mode is active
$login = sotf_Utils::getParameter('login');		//login page
$id = sotf_Utils::getParameter('id');			//programme view mode (programmes page)
if ($portal->isAdmin($user->getId()))		//only for admin users
{
	$playlist = sotf_Utils::getParameter('playlist');	//programme editor mode
	$style = sotf_Utils::getParameter('style');		//style editor mode
	$edit = sotf_Utils::getParameter('edit');		//edit mode
	$admin = sotf_Utils::getParameter('admin');		//admin page

	////settings for the portal, table and others
	$settings = $_SESSION["settings"];			//load current settings from session
	if ($settings["table"] == "")
	{
		$settings = $portal->loadSettings();	//if not found load saved portal
		$_SESSION['old_settings'] = $settings;	//save as old settings
	}
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
		$settings["portal"]["picture"] = sotf_Utils::getParameter('menu_picture');
		$settings["portal"]["css"] = sotf_Utils::getParameter('portal_css');
	
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
	
		$style = "1";		//do not go from the style page

		//goto after save
		//if (sotf_Utils::getParameter('goto') == "programmes") $playlist = true;
		//elseif (sotf_Utils::getParameter('goto') == "edit") $edit = true;
		//elseif (sotf_Utils::getParameter('goto') == "view") $view = true;
		//elseif (sotf_Utils::getParameter('goto') == "admin") $admin = true;
	}
	elseif (sotf_Utils::getParameter('file_upload'))	//Upload file (picture or CSS) on edit style page
	{
		//sotf_Utils::getParameter('file_name')
		$portal->uploadFile($_FILES['file_file']['tmp_name'], $_FILES['file_file']['name'], NULL, sotf_Utils::getParameter('file_name'));
		$style = "1";
	}
	elseif (sotf_Utils::getParameter('save_changes'))		//admin page save button pressed
	{
		if ($admin)			//if on admin page
		{
			if (sotf_Utils::getParameter('stylesheet')) $settings["css"] = true;
				else $settings["css"] = false;
			if (sotf_Utils::getParameter('rating')) $settings["rating"] = true;
				else $settings["rating"] = false;
			if (sotf_Utils::getParameter('chat')) $settings["chat"] = true;
				else $settings["chat"] = false;
			if (sotf_Utils::getParameter('change_password'))
			{
				$password_old = sotf_Utils::getParameter('password_old');
				$password_new1 = sotf_Utils::getParameter('password_new1');
				$password_new2 = sotf_Utils::getParameter('password_new2');
				if ($password_new1 == "") $error .= $page->getlocalized("password_empty");
				elseif ($password_new1 != $password_new2) $error .= $page->getlocalized("password_different");
				elseif ($portal->changePortalPassword($password_old, $password_new1)) $error .= $page->getlocalized("password_incorrect");
				else $error .= $page->getlocalized("password_changed");
			}
		}
		if ($portal->saveSettings($settings) == 1) $_SESSION['old_settings'] = $settings;	//if saved delete from session
	}
	elseif (sotf_Utils::getParameter('insert_row_x'))		//insert row button pressed on edit page
	{
		//var_dump((substr(sotf_Utils::getParameter('edit'),1)));
		$portal->insertRow((substr(sotf_Utils::getParameter('edit'),1)));
	}
	elseif (sotf_Utils::getParameter('delete_row_x'))		//insert row button pressed on edit page
	{
		//var_dump((substr(sotf_Utils::getParameter('edit'),1)));
		$portal->deleteRow((substr(sotf_Utils::getParameter('edit'),1)));
	}
	elseif (sotf_Utils::getParameter('create_new_list'))		//create new page button pressed on programmes editor page
	{
		$portal->createNewPlaylist(sotf_Utils::getParameter('new_list_name'));
	}
	elseif (sotf_Utils::getParameter('copy_selected'))		//copy button pressed on programmes editor page
	{
		if (count(sotf_Utils::getParameter('selected')) > 0)
		foreach (sotf_Utils::getParameter('selected') as $prg_id)
		{
			$portal->addProgrammeToList($prg_id, sotf_Utils::getParameter('destination'));
		}
	}
	elseif (sotf_Utils::getParameter('move_selected'))		//move button pressed on programmes editor page
	{
		if (count(sotf_Utils::getParameter('selected')) > 0)
		foreach (sotf_Utils::getParameter('selected') as $prg_id)
		{
			$portal->addProgrammeToList($prg_id, sotf_Utils::getParameter('destination'));
			$portal->deleteProgrammeFromList($prg_id, substr($_SESSION['prglist'], 1));		//first char is p for programme list
		}
	}
	elseif (sotf_Utils::getParameter('delete_selected'))		//delete selected button pressed on programmes editor page
	{
		if (count(sotf_Utils::getParameter('selected')) > 0)
		foreach (sotf_Utils::getParameter('selected') as $prg_id)
		{
			$portal->deleteProgrammeFromList($prg_id, substr($_SESSION['prglist'], 1));		//first char is p for programme list
		}
	}
	elseif (sotf_Utils::getParameter('upload_file'))		//upload button pressed on programmes editor page
	{
		$prg_id = sotf_Utils::getParameter('upload_file');		//id of the program to which the file belongs
		$portal->uploadFile($_FILES['uploaded_file_'.$prg_id]['tmp_name'], $_FILES['uploaded_file_'.$prg_id]['name'], $prg_id);
		$page->redirect($_SERVER["PHP_SELF"]."?playlist=1&anchor=".$prg_id);		//redirect page, prevent resend of data
	}
	elseif (sotf_Utils::getParameter('delete_file'))		//delete button pressed on programmes editor page
	{
		$portal->deleteFile(sotf_Utils::getParameter('delete_file'), sotf_Utils::getParameter('prgid'));
	}

	if ($playlist)						//on programmes editor page
	{
		$filter = $_SESSION['filter'];			//filter dropdown box
		$prglist = $_SESSION['prglist'];		//pgogrammes list dropdown box
	
		if ($prglist == "queries" OR $prglist == "playlists") $prglist = "current";
	
		$type = $prglist{0};			//first char indicates wther its a query or statik prg list
		$value = substr($prglist,1);		//the others are the data
		if ($type == "q")
		{
			$results = $portal->runQuery($value);
		}
		elseif ($type == "p")
		{
			$results = $portal->runPlaylist($value);
		}
		else	//current programmes
		{
			$results=$portal->getProgrammesOnPortal();
		}
	
		$fields = $portal->getAllFieldnames();
		$selected_result = array();
		$item = array();

		foreach($results as $result)
		{
			$prgprop = $portal->getPrgProperties($result[id]);
			//filter can be: all teaser text something
			if (($filter == "teaser") AND ($prgprop['teaser'] != "")) continue;	//if only the ones without teaser are needed
			if (($filter == "text") AND ($prgprop['text'] != "")) continue;		//if only the ones without text are needed
			if (($filter == "something") AND ($prgprop['teaser'] != "") AND ($prgprop['text'] != "")) continue;	//if only the ones without somting are needed
			
			if ($prgprop['teaser'] != "") {$item['teaser'] = substr($prgprop['teaser'], 0, 200);if (strlen($item['teaser']) == 200) $item['teaser'].="...";}
				else $item['teaser'] = $page->getlocalized("none");
			if ($prgprop['text'] != "") {$item['text'] = substr($prgprop['text'], 0, 200);if (strlen($item['text']) == 200) $item['text'].="...";}
				else $item['text'] = $page->getlocalized("none");

			foreach($result as $key => $value)
				if (array_key_exists($key, $fields) AND $key != 'title')		//title is presented on a diferent level
					if ($key == 'language' AND $value != "") $values[$fields[$key]] = $page->getlocalized($value);	//language need to be translated
					else $values[$fields[$key]] = htmlspecialchars($value);
			$item['title'] = htmlspecialchars($result['title']);
			$item['id'] = $result['id'];
			$item['icon'] = $result['icon'];
			$item['files'] = $prgprop['files'];
			$item['values'] = $values;
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
	
		$smarty->assign("filters", $portal->getFilters());
		$smarty->assign("filter", $filter);

		$smarty->assign("static_lists", $portal->getPlaylists());
	}

	$settings["table"] = $portal->getTable();		//save current table
	if ($_SESSION['old_settings'] != $settings)		//if there is unsaved information save to session
	{
		////save cuttent portal table to the session
		$_SESSION["settings"] = $settings;			//save current settings
		$smarty->assign("unsaved", true);			//set in smary as well
	}
	else
	{
		unset($_SESSION["settings"]);		//else destroy settings in session
		session_unregister("settings");		//unregister it from session
	}

}	////end of admin section

$activate = sotf_Utils::getParameter('activate');

if ($id)	//if programmes view
{
	$comments = $portal->getComments($id);

	if ($user->loggedIn())		//only for logged in users
	{
		if (sotf_Utils::getParameter('add_comment'))	//Send button pressed
		{
			$reply_to = sotf_Utils::getParameter('reply_to');
			if ($reply_to == "root") $reply_to = NULL;
			$portal->addComment($id, $user->getId(), $reply_to, sotf_Utils::getParameter('title'), sotf_Utils::getParameter('value'));
			$page->redirect($_SERVER["PHP_SELF"]."?id=".$id);		//redirect page, prevent resend of data
		}
		elseif (sotf_Utils::getParameter('comment'))		//add comment or reply button (link) pressed
		{
			$reply_to = sotf_Utils::getParameter('comment');
			$page->redirect($_SERVER["PHP_SELF"]."?id=".$id."&reply_to=".$reply_to."#edit");		//redirect page, prevent resend of data
		}
		elseif (sotf_Utils::getParameter('delete_comment'))	//delete a comment (admin only)
		{
			if ($portal->isAdmin($user->getId())) $portal->deleteComment($id, $user->getId(), sotf_Utils::getParameter('delete_comment'));
			$comments = $portal->getComments($id);		//reread comments
		}
		elseif (sotf_Utils::getParameter('rate_it'))	//programme rating
		{
			$page->redirect($_SERVER["PHP_SELF"]."?id=".$id);		//redirect page, prevent resend of data
		}
		elseif (sotf_Utils::getParameter('delete_file'))	//delete a file that is associated to the programme
		{
			if ($portal->isAdmin($user->getId())) $portal->deleteFile(sotf_Utils::getParameter('delete_file'), $id);
		}
		$reply_to = sotf_Utils::getParameter('reply_to');
		$smarty->assign('reply_to', $reply_to);
		$smarty->assign('reply_title', $comments[$reply_to]['title']);
	}

	$smarty->assign('comments', $comments);
	$result = $portal->getProgrammes(array($id));
	$result = $result[0];

	$fields = $portal->getAllFieldnames();

	$prgprop = $portal->getPrgProperties($id);
	$programme['teaser'] = $prgprop['teaser'];
	$programme['text'] = nl2br($prgprop['text']);
	$programme['files'] = $prgprop['files'];
	
	foreach($result as $key => $value)
		if (array_key_exists($key, $fields) AND $key != 'title')		//title is presented on a diferent level
			if ($key == 'language' AND $value != "") $values[$fields[$key]] = $page->getlocalized($value);	//language need to be translated
			else $values[$fields[$key]] = $value;
	$programme['title'] = $result['title'];
	$programme['id'] = $result['id'];
	$programme['icon'] = $result['icon'];
	$programme['values'] = $values;

	$smarty->assign('programme', $programme);

}
elseif (sotf_Utils::getParameter('register_new_user'))
{
	$desired_username = sotf_Utils::getParameter('desired_username');
	$desired_password = sotf_Utils::getParameter('desired_password');
	$desired_password2 = sotf_Utils::getParameter('desired_password2');
	$email_address = sotf_Utils::getParameter('email_address');
	if (($desired_username == "") OR ($desired_password == "") OR ($email_address == ""))
	{
		$smarty->assign('reply', $page->getlocalized("user_not_added"));
	}
	elseif ($desired_password != $desired_password2)
	{
		$smarty->assign('reply', $page->getlocalized("passwords_are_different"));
	}
	elseif ($user->addNewUser($portal->getId(), $desired_username, $desired_password, $email_address))
	{
		$smarty->assign('reply', $page->getlocalized("user_added"));
		$desired_username = "";
		$email_address = "";
	}
	else $smarty->assign('reply', $page->getlocalized("user_exists"));
	$smarty->assign('desired_username', $desired_username);
	$smarty->assign('email_address', $email_address);
	$login = "1";
}
elseif (sotf_Utils::getParameter('resend_a'))		//if resend activisation number pressed on login page
{
	$uname = sotf_Utils::getParameter('username');
	$smarty->assign('uname', $uname);
	$login = "1";
	$activate = 1;
	$user->sendMail($portal->getId(), $uname, "activate");
	$smarty->assign('reply', $page->getlocalized("a_sent"));
}
elseif (sotf_Utils::getParameter('resend_pass'))		//if resend password pressed on login page
{
	$uname = sotf_Utils::getParameter('username');
	$smarty->assign('uname', $uname);
	$login = "1";
	$user->sendMail($portal->getId(), $uname, "password");
	$smarty->assign('reply', $page->getlocalized("pass_sent"));
}


$smarty->assign('activate', $activate);
$smarty->assign('uname', sotf_Utils::getParameter('uname'));
if ($login == "2") $smarty->assign('bad_login', true);

//select subpage
if ($login) $subpage="login";
elseif ($playlist) $subpage="playlist";
elseif ($style) $subpage="style";
elseif ($edit) $subpage="edit";
elseif ($id) $subpage="id";
elseif ($admin) $subpage="admin";
else {$subpage="view";$view = true;}	//set default to view mode
$smarty->assign("subpage", $subpage);

$smarty->assign("login", $login);	//login page
$smarty->assign("playlist", $playlist);	//in editstyle mode
$smarty->assign("style", $style);	//in editstyle mode
$smarty->assign("edit", $edit);		//in edit mode
$smarty->assign("view", $view);		//in view result mode
$smarty->assign("id", $id);		//in view programme mode
$smarty->assign("admin", $admin);	//admin page

//prevent browsers from reload/reprocess the page

//it seems an IE bug, but in IE6 the anchor disappears when redirecting after file upload...
$anchor = sotf_Utils::getParameter('anchor');
if (isset($anchor)) $page->redirect($_SERVER["PHP_SELF"]."?".$subpage."=3#".$anchor);		//redirect page

if ((count($_POST) > 0) AND !$login)
{
	$_SESSION['error'] = $error;			//needed after reload
	$page->redirect($_SERVER["PHP_SELF"]."?".$subpage."=2");		//redirect page
}


////SMARTY variables
$smarty->assign("table", $portal->getTable());		//current layout table


//user rights and options
$smarty->assign("is_admin", $portal->isAdmin($user->getId()));		//true if admin
$smarty->assign("is_logged_in", $user->loggedIn());			//true if logged in
$smarty->assign("username", $user->getName());				//username (if logged in)
$smarty->assign("back", sotf_Utils::getParameter('back'));		//true if came from programmes editor page to the view programme page


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

$smarty->assign("error", $_SESSION['error']);			//user (error) messages
$_SESSION['error'] = $error;					//delete the errormessages from session

if ($settings["css"] == true) $smarty->assign("home_css", $settings["home"]["css"]);


//$smarty->assign("settings", $settings);		//*********************DEBUG
//$smarty->assign("numbers", $portal->getNumbers());		//OLD finction
//$smarty->assign("rowlength", $portal->getRowLength());	//OLD function

$page->send("portal.htm");
//$page->send();

?>
