<?php

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: Máté Pataki, András Micsik
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 * 
 */

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
$preferences = sotf_Utils::getParameter('preferences');			//user preferences mode

if ($portal->isAdmin($user->getId()))		//only for admin users
{
	$playlist = sotf_Utils::getParameter('playlist');	//programme editor mode
	$style = sotf_Utils::getParameter('style');		//style editor mode
	$edit = sotf_Utils::getParameter('edit');		//edit mode
	$admin = sotf_Utils::getParameter('admin');		//admin page

	////settings for the portal, table and others
	if (($_SESSION["portal_name"] == $portal_name) OR !isset($_SESSION["portal_name"])) $settings = $_SESSION["settings"];			//load current settings from session

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
	$save_style = sotf_Utils::getParameter('save_style');
	$file_upload = sotf_Utils::getParameter('file_upload');
	$save_changes = sotf_Utils::getParameter('save_changes');
	if (!$save_style AND !$file_upload AND sotf_Utils::getParameter('update_and_save'))
	{	//if save button (menu) pressed on style editor page get form data and save also
		$save_style = true;
		$save_changes = true;
	}

	if ($save_style)		//in edit style mode SAVE button pressed
	{
		//Portal menu
		$settings["portal"]["bg1"] = $portal->correctColor(sotf_Utils::getParameter('portal_bg1'));
		$settings["portal"]["bg2"] = $portal->correctColor(sotf_Utils::getParameter('portal_bg2'));
		$settings["portal"]["font"] = $portal->correctColor(sotf_Utils::getParameter('portal_font'));
		$settings["portal"]["picture"] = sotf_Utils::getParameter('menu_picture');
		$settings["portal"]["picture_align"] = sotf_Utils::getParameter('menu_picture_align');
		if (sotf_Utils::getParameter('menu_picture_tiled')) $settings["portal"]["picture_tiled"] = true;
			else $settings["portal"]["picture_tiled"] = false;
		$image = @getimagesize($settings["portal"]["picture"]);
		if ($image == false) $settings["portal"]["picture_tiled"] = false;
		$settings["portal"]["picture_height"] = $image[1];
		if ($settings["css"]) $settings["portal"]["css"] = sotf_Utils::getParameter('portal_css');
	
		//Portal home
		$settings["home"]["bg"] = $portal->correctColor(sotf_Utils::getParameter('home_bg'));
		$settings["home"]["font"] = $portal->correctColor(sotf_Utils::getParameter('home_font'));
		$settings["home"]["link"] = $portal->correctColor(sotf_Utils::getParameter('home_link'));
		$settings["home"]["alink"] = $portal->correctColor(sotf_Utils::getParameter('home_alink'));
		$settings["home"]["vlink"] = $portal->correctColor(sotf_Utils::getParameter('home_vlink'));
		$settings["home"]["wall"] = sotf_Utils::getParameter('home_wall');
		if ($settings["css"]) $settings["home"]["css"] = sotf_Utils::getParameter('home_css');
	
		//Programmes page
		$settings["programmes"]["bg"] = $portal->correctColor(sotf_Utils::getParameter('programmes_bg'));
		$settings["programmes"]["font"] = $portal->correctColor(sotf_Utils::getParameter('programmes_font'));
		$settings["programmes"]["link"] = $portal->correctColor(sotf_Utils::getParameter('programmes_link'));
		$settings["programmes"]["alink"] = $portal->correctColor(sotf_Utils::getParameter('programmes_alink'));
		$settings["programmes"]["vlink"] = $portal->correctColor(sotf_Utils::getParameter('programmes_vlink'));
		$settings["programmes"]["wall"] = sotf_Utils::getParameter('programmes_wall');
		if ($settings["css"]) $settings["programmes"]["css"] = sotf_Utils::getParameter('programmes_css');
	
		$style = "1";		//do not go from the style page

		//goto after save
		//if (sotf_Utils::getParameter('goto') == "programmes") $playlist = true;
		//elseif (sotf_Utils::getParameter('goto') == "edit") $edit = true;
		//elseif (sotf_Utils::getParameter('goto') == "view") $view = true;
		//elseif (sotf_Utils::getParameter('goto') == "admin") $admin = true;
	}
	elseif ($file_upload)	//Upload file (picture or CSS) on edit style page
	{
		//sotf_Utils::getParameter('file_name')
		$q = $portal->uploadFile($_FILES['file_file']['tmp_name'], $_FILES['file_file']['name'], NULL, sotf_Utils::getParameter('file_name'));
		if ($q === "QUOTA") $error .= $page->getlocalized("quota_exceeded");
		$style = "1";
	}

	if ($save_changes)		//admin page save button pressed
	{
		$portal->addEvent("portal_updated", "$portal_name");
		if ($admin)			//if on admin page
		{
			if ($settings["rating"])	//if rating enabled check it (else it is disabled)
			if (sotf_Utils::getParameter('a_rating')) $settings["a_rating"] = true;
				else $settings["a_rating"] = false;
			if ($settings["chat"])		//if chat (comments) enabled check it (else it is disabled)
			if (sotf_Utils::getParameter('a_chat')) $settings["a_chat"] = true;
				else $settings["a_chat"] = false;

			if (sotf_Utils::getParameter('stylesheet')) $settings["css"] = true;
				else $settings["css"] = false;
			if (sotf_Utils::getParameter('rating')) $settings["rating"] = true;
				else $settings["rating"] = false;
			if (sotf_Utils::getParameter('chat')) $settings["chat"] = true;
				else $settings["chat"] = false;

			if (sotf_Utils::getParameter('change_password'))
			{
				$password_new1 = sotf_Utils::getParameter('password_new1');
				$password_new2 = sotf_Utils::getParameter('password_new2');
				if ($password_new1 == "") $error .= $page->getlocalized("password_empty");
				elseif ($password_new1 != $password_new2) $error .= $page->getlocalized("password_different");
				elseif ($portal->changePortalPassword($password_new1)) $error .= $page->getlocalized("password_incorrect");
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
	elseif (sotf_Utils::getParameter('delete_playlist'))		//delete this list link pressed on programmes editor page
	{
		$portal->deletePlaylist(sotf_Utils::getParameter('delete_playlist'));
		$_SESSION['prglist']='current';
	}
	elseif (sotf_Utils::getParameter('delete_query'))		//delete this query link pressed on programmes editor page
	{
		$portal->deleteQuery(sotf_Utils::getParameter('delete_query'));
		$_SESSION['prglist']='current';
	}
	elseif (sotf_Utils::getParameter('upload_file'))		//upload button pressed on programmes editor page
	{
		$prg_id = sotf_Utils::getParameter('upload_file');		//id of the program to which the file belongs
		$q = $portal->uploadFile($_FILES['uploaded_file_'.$prg_id]['tmp_name'], $_FILES['uploaded_file_'.$prg_id]['name'], $prg_id);
		if ($q === "QUOTA") $_SESSION['error'] .= $page->getlocalized("quota_exceeded");
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
		if ($prglist == "with_files")
		{
			$results = $portal->getProgrammesWithFiles();
			$list = array();
			foreach ($results as $prg) $list[] = $prg['id'];

			$files = $portal->getFilesWithoutProgrammes($list);
			if (count($files) > 0) $smarty->assign("other_files", $files);
		}
		elseif ($type == "q")
		{
			$results = $portal->runQuery($value);
			$q = $portal->getQueries();
			$smarty->assign("query_name", $q[$value]);
			$smarty->assign("is_query", $value);
		}
		elseif ($type == "p")
		{
			$results = $portal->runPlaylist($value);
			if ($value != "unsorted")
			{
				$smarty->assign("is_playlist", $value);	//if not the Uploaded prglist (which can not be deleted)
				$p = $portal->getPlaylists();
				$smarty->assign("playlist_name", $p[$value]);
			}
		}
		else	//current programmes
		{
			$results=$portal->getProgrammesOnPortal();
		}
	
		$fields = $portal->getAllFieldnames();
		$selected_result = array();
		$item = array();
		$item['listen'] = array();

		foreach($results as $result)
		{
			$prgprop = $portal->getPrgProperties($result[id]);
			//filter can be: all teaser text something
			if (($filter == "teaser") AND ($prgprop['teaser'] != "")) continue;	//if only the ones without teaser are needed
			if (($filter == "text") AND ($prgprop['text'] != "")) continue;		//if only the ones without text are needed
			if (($filter == "something") AND ($prgprop['teaser'] != "") AND ($prgprop['text'] != "")) continue;	//if only the ones without somting are needed
			
			if ($prgprop['teaser'] != "") {$item['teaser'] = substr($prgprop['teaser'], 0, 200);if (strlen($item['teaser']) == 200) $item['teaser'].="...";}
				//else $item['teaser'] = $page->getlocalized("no_teaser");
			if ($prgprop['text'] != "") {$item['text'] = substr($prgprop['text'], 0, 200);if (strlen($item['text']) == 200) $item['text'].="...";}
				//else $item['text'] = $page->getlocalized("no_text");

			foreach($result as $key => $value)
				if (array_key_exists($key, $fields) AND $key != 'title')		//title is presented on a diferent level
					if ($key == 'language' AND $value != "")
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
				$afile['mime_type'] = $audioFiles['mime_type'];
				//$afile['link'] = "listen.php/audio.m3u?id=".$audioFiles['prog_id']."&fileid=".$audioFiles['id'];
				$afile['link'] = "listen.php/id__".$audioFiles['prog_id']."/fileid__".$audioFiles['id']."/audio.m3u";
				$afile['filesize'] = $audioFiles['filesize'];
				$afile['play_length'] = $audioFiles['play_length'];
				$afile['kbps'] = $audioFiles['kbps'];
				$afile['vbr'] = $audioFiles['vbr'];
				$item['listen'][] = $afile;
			}
			$item['title'] = htmlspecialchars($result['title']);
			$item['id'] = $result['id'];
			$item['icon'] = $result['icon'];
			$item['files'] = $prgprop['files'];
			$item['comments'] = $prgprop['comments'];
			$item['rating'] = $prgprop['rating'];
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
				array(
					"current" => "&nbsp;-&nbsp;&nbsp;".$page->getlocalized("prg_on_portal"),
					"with_files" => "&nbsp;-&nbsp;&nbsp;".$page->getlocalized("prg_with_files"),
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
		$_SESSION["portal_name"] = $portal_name;			//to ensure that they will only be loaded for the same portal
		$smarty->assign("unsaved", true);			//set in smary as well
	}
	else
	{
		unset($_SESSION["settings"]);		//else destroy settings in session
		session_unregister("settings");		//unregister it from session
	}

}	////end of admin section

$activate = sotf_Utils::getParameter('activate');

if ($user->loggedIn())			//if logged in
{
	if (sotf_Utils::getParameter('change_user_password'))	//change_user_password button pressed
	{
		$password_old = sotf_Utils::getParameter('password_old');
		$password_new1 = sotf_Utils::getParameter('password_new1');
		$password_new2 = sotf_Utils::getParameter('password_new2');
		if ($password_new1 == "") $error .= $page->getlocalized("password_empty");
		elseif ($password_new1 != $password_new2) $error .= $page->getlocalized("password_different");
		elseif ($user->changeUserPassword($portal_id, $user->getName(), $password_old, $password_new1)) $error .= $page->getlocalized("user_password_incorrect");
		else $error .= $page->getlocalized("user_password_changed");
//		$preferences = 1;
	}
}

if ($id)	//if programmes view
{
	$comments = $portal->getComments($id);
	$rating = new Rating();

	$portal->addEvent("visit", array("prog_id" => $id, "user_name" => $user->getName(),"user_email" => $user->getEmail(), "host" => getHostName(), "authkey" => $page->getAuthKey()));

	if ($portal->isAdmin($user->getId()))	//if admin user
	{
		if (sotf_Utils::getParameter('delete_comment'))	//delete a comment (admin only)
		{
			$portal->deleteComment($id, $user->getId(), sotf_Utils::getParameter('delete_comment'));
			$comments = $portal->getComments($id);		//reread comments
		}
		elseif (sotf_Utils::getParameter('delete_file'))	//delete a file that is associated to the programme
		{
			$portal->deleteFile(sotf_Utils::getParameter('delete_file'), $id);
		}
	}

	if ( ($user->loggedIn()) OR ($settings["a_chat"]) )	//if logged in or anonym chat enabled
	{
		if ($settings["chat"])
		if (sotf_Utils::getParameter('add_comment'))	//Send button pressed
		{
			$reply_to = sotf_Utils::getParameter('reply_to');
			$value = sotf_Utils::getParameter('value');
			$title = sotf_Utils::getParameter('title');

			//if (strpos($email, "@") == false)		//if email not valid
			//	$page->redirect($_SERVER["PHP_SELF"]."?id=".$id."&reply_to=".$reply_to."&value=".urlencode($value)."#edit");		//redirect page, prevent resend of data
			if ($user->loggedIn())
			{
				$email = $user->getEmail();
				$user_id = $user->getId();
			}
			else
			{
				$email = sotf_Utils::getParameter('email');
				$_SESSION['email'] = $email;
				$user_id = NULL;
			}

			if ($reply_to == "root") $reply_to = NULL;
			$portal->addComment($id, $user_id, $reply_to, $title, $value, $email);

			$page->redirect($_SERVER["PHP_SELF"]."?id=".$id);		//redirect page, prevent resend of data
		}
		elseif (sotf_Utils::getParameter('comment'))		//add comment or reply button (link) pressed
		{
			$reply_to = sotf_Utils::getParameter('comment');
			$page->redirect($_SERVER["PHP_SELF"]."?id=".$id."&reply_to=".$reply_to."#edit");		//redirect page, prevent resend of data
		}
		$reply_to = sotf_Utils::getParameter('reply_to');
		$smarty->assign('reply_to', $reply_to);
		$smarty->assign('reply_title', $comments[$reply_to]['title']);
		$smarty->assign('email', $_SESSION['email']);
	}

	if ($settings["rating"] AND ($user->loggedIn() OR $settings["a_rating"]))	//if logged in or anonym rating enabled
	{
		$value = (integer)sotf_Utils::getParameter('rating');
		if (sotf_Utils::getParameter('rate_it') AND ($value != 0))	//programme rating
		{
			$rating->setRating($id, $value);
			$r = $rating->getRating($id);
			$r['prog_id'] = $id;
			$r['user_name'] = $user->getName();
			$r['user_email'] = $user->getEmail();
			$r['host'] = getHostName();
			$r['authkey'] = $page->getAuthKey();
			$portal->addEvent("rating", $r);
			$page->redirect($_SERVER["PHP_SELF"]."?id=".$id);		//redirect page, prevent resend of data
		}
	}

	$smarty->assign('comments', $comments);
	$result = $portal->getProgrammes(array($id));

	$result = $result[0];

	if ($result === NULL) $page->redirect($_SERVER["PHP_SELF"]);	//if programme does not exsists go back to the page TODO: maybe an error page that programme not found

	$fields = $portal->getAllFieldnames();

	$prgprop = $portal->getPrgProperties($id);
	$programme['teaser'] = $prgprop['teaser'];
	$programme['text'] = nl2br($prgprop['text']);
	$programme['listen'] = array();
	$programme['download'] = array();

//	$programme['files'] = $prgprop['files'];

	foreach ($prgprop['files'] as $filename => $file)
	{
		$MAX_WIDTH = 100;		
		$prop = getimagesize($_SERVER['DOCUMENT_ROOT'].$file);
		$type = $prop[2];		//1 = GIF, 2 = JPG, 3 = PNG, 4 = SWF, 5 = PSD, 6 = BMP, 7 = TIFF(intel byte order), 8 = TIFF(motorola byte order), 9 = JPC, 10 = JP2, 11 = JPX, 12 = JB2, 13 = SWC, 14 = IFF
		$image['name'] = $filename;
		$image['location'] = $file;
		$image['width'] = $prop[0];
		$image['height'] = $prop[1];
		if ($image['width'] > $MAX_WIDTH)
		{
			$image['height'] = $MAX_WIDTH/$image['width']*$image['height'];
			$image['width'] = $MAX_WIDTH;
		}
		if ( ($type == 1) OR ($type == 2) OR ($type == 3) OR ($type == 4)) $programme['pictures'][] = $image;
		else $programme['files'][$filename] = $file;
	}

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
			else $values[$fields[$key]] = $value;

	foreach($result['audioFiles'] as $audioFiles)
	{
		$afile['mime_type'] = $audioFiles['mime_type'];
		$afile['link'] = "listen.php/audio.m3u?id=".$audioFiles['prog_id']."&fileid=".$audioFiles['id'];
		$afile['filesize'] = $audioFiles['filesize'];
		$afile['play_length'] = $audioFiles['play_length'];
		$afile['kbps'] = $audioFiles['kbps'];
		$afile['vbr'] = $audioFiles['vbr'];
		$programme['listen'][] = $afile;
	}

	foreach($result['downloadFiles'] as $downloadFiles)
	{
		$dfile['mime_type'] = $downloadFiles['mime_type'];
		$dfile['link'] = "getFile.php/".$downloadFiles['filename']."?id=".$downloadFiles['prog_id']."&filename=".$downloadFiles['filename']."&audio=1";
		$dfile['filesize'] = $downloadFiles['filesize'];
		$dfile['play_length'] = $downloadFiles['play_length'];
		$dfile['kbps'] = $downloadFiles['kbps'];
		$dfile['vbr'] = $downloadFiles['vbr'];
		$programme['download'][] = $dfile;
	}
	$programme['title'] = $result['title'];
	$programme['id'] = $result['id'];
	$programme['icon'] = $result['icon'];
	$programme['values'] = $values;

if ($_SERVER["REMOTE_ADDR"] == "193.225.87.196")
{
//	print("<pre>");
//	var_dump($programme);
}

	$smarty->assign('programme', $programme);
/*
				 RATING_OUTPUT => $rtext,
a				 RATING_VALUE => $array['rating_value'],
a				 RATING_COUNT => $array['rating_count_reg'] + $array['rating_count_anon'],
				 RATING_COUNT_REG => $array['rating_count_reg'],
				 RATING_COUNT_ANON => $array['rating_count_anon']
*/
	$r = $rating->getRating($id);
	$average = round((float)$r['RATING_VALUE']);
	$smarty->assign('rating_average', $page->getlocalized("rating_".$average));
	$smarty->assign('users_rated', $r['RATING_COUNT']);
	$smarty->assign('ratings', $rating->getRatings());
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
		$portal->addEvent("users", $user->countUsers($portal_id));
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
elseif ($preferences)
{
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
elseif ($preferences) $subpage="preferences";
else {$subpage="view";$view = true;}	//set default to view mode
$smarty->assign("subpage", $subpage);

$smarty->assign("login", $login);	//login page
$smarty->assign("playlist", $playlist);	//in editstyle mode
$smarty->assign("style", $style);	//in editstyle mode
$smarty->assign("edit", $edit);		//in edit mode
$smarty->assign("view", $view);		//in view result mode
$smarty->assign("id", $id);		//in view programme mode
$smarty->assign("admin", $admin);	//admin page
$smarty->assign("preferences", $preferences);	//user preferences page

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
$smarty->assign("a_chat", $settings["a_chat"]);			//anonym chat enabled
$smarty->assign("a_rating", $settings["a_rating"]);		//anonym rating enabled

$smarty->assign("colors", $portal->getColors());
$smarty->assign("files", $portal->getUploadedFiles());

$smarty->assign("error", $_SESSION['error']);			//user (error) messages
$_SESSION['error'] = $error;					//delete the errormessages from session

if ($settings["css"] == true)
{
	$smarty->assign("home_css", $settings["home"]["css"]);
	$smarty->assign("portal_css", $settings["portal"]["css"]);
	$smarty->assign("programmes_css", $settings["programmes"]["css"]);
}


//$smarty->assign("settings", $settings);		//*********************DEBUG
//$smarty->assign("numbers", $portal->getNumbers());		//OLD finction
//$smarty->assign("rowlength", $portal->getRowLength());	//OLD function

$page->send("portal.htm");
//$page->send();

?>
