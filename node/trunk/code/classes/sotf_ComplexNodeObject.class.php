<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-

/*
 * $Id$
 *
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri
 *					at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

class sotf_ComplexNodeObject extends sotf_NodeObject {

	/** constructor */
	function sotf_ComplexNodeObject($tablename, $id='', $data='') {
		$this->sotf_NodeObject($tablename, $id, $data);
	}						

	/************************************************
	 *      GENERAL
	 ************************************************/

	/** Abstract methods, which should be implemented in all subclasses:*/
	//function getDir()
	//function getMetaDir()
	//function checkDirs

	/** overrides function in sotf_NodeObject */
	/*
	function isLocal() {
		$retval = is_dir($this->getDir()); 
		debug("isLocal2", $retval);
		return $retval;
	}
	*/

	function create() {
	  $succ = parent::create();
	  if($succ) {
		 $this->checkDirs();
		 $this->saveMetadataFile();
	  }
	  return $succ;
	}
	
	function delete() {
	  if($this->isLocal()) {
		 // delete files from the repository
		 debug("deleting: ", $this->getDir());
		 sotf_Utils::erase($this->getDir());
		 // delete from sql db
	  }
	  return parent::delete();
	}

	function update() {
	  parent::update();
	  if(parent::isLocal()) {
		 $this->checkDirs();
		 $this->saveMetadataFile();
	  }
	}

	/** caches icon for object, and adds indicator flag for Smarty templates whether there is an icon */
	function getAllWithIcon() {
		$retval = $this->getAll();
		$retval['icon'] = sotf_Blob::cacheIcon($retval['id']);
		return $retval;
	}

	function cacheIcon() {
	  return sotf_Blob::cacheIcon($this->id);
	}

	function getStationId() {
		//debug("class", get_class($this));
		switch($this->tablename) {
		case "sotf_stations":
		  return $this->id;
		default:
		  return $this->get('station_id');
		}
	}

	/************************************************
	 *      METADATA
	 ************************************************/
	
	function saveMetadataFile() {
	  global $permissions;

	  $name = get_class($this);
	  $name = str_replace("sotf_", "", $name);
	  $xml = "<$name>";
	  $xml .= sotf_Utils::writeXML('data', $this->data, 1);
	  $roles = $this->getRoles();
	  $xml .= sotf_Utils::writeXML('role', $roles, 1);
	  $perms = $permissions->listUsersAndPermissions($this->id);
	  $xml .= sotf_Utils::writeXML('permission', $perms, 1);
	  $xml = $xml . "\n</$name>\n";
	  $file = $this->getMetaDir() . '/metadump.xml';
	  debug("dumping metadata xml in", $file);
	  $fp = fopen("$file", "w");
	  fwrite($fp, $xml);
	  fclose($fp);
	  return true;
	}

	/************************************************
	 *      LANGUAGE HACK
	 ************************************************/

	/** can be static */
	function getLanguagesArray($languages = '') {
	  if(!$languages)
		 $languages = $this->get('language');
	  if(!empty($languages)) {
		 $langs = explode(',',$languages);
		 return $langs;
	  }
	  return array();
	}

	/** can be static */
	function getLanguagesLocalized($languages = '') {
	  global $page;
	  if(!$languages)
		 $languages = $this->get('language');
	  if(!empty($languages)) {
		 $langs = explode(',',$languages);
		 for($i=0; $i<count($langs); $i++) {
			if($i>0)
			  $lstring .= ', ';
			$lstring .= $page->getlocalized($langs[$i]);
		 }
	  }
	  debug("lstring", $lstring);
	  return $lstring;
	}
	
	// language hack
	function setLanguageWithParams() {
	  $langs = sotf_Utils::getParameter('language1');
	  $l2 = sotf_Utils::getParameter('language2');
	  $l3 = sotf_Utils::getParameter('language3');
	  if($l2) {
		 $langs .= ",$l2";
		 if($l3) {
			$langs .= ",$l3";
		 }
	  }  
	  $this->set('language', $langs);
	}

	function getLanguageSelectBoxes() {
	  global $smarty, $config, $page;
	  for($i=0; $i<count($config['languages']); $i++) {
		 $langNames[$config['languages'][$i]] = $page->getlocalized($config['languages'][$i]);
	  }
	  asort($langNames);
	  $smarty->assign('LANG_CODES', array_keys($langNames));
	  $smarty->assign('LANG_NAMES', array_values($langNames));
	  $langs = explode(',',$this->get('language'));
	  $smarty->assign('PRG_LANG1', $langs[0]);
	  $smarty->assign('PRG_LANG2', $langs[1]);
	  $smarty->assign('PRG_LANG3', $langs[2]);
	}

	function get2LetterLanguageCode($languages = '') {
	  if(!$languages)
		 $languages = $this->get('language');
	  if(!empty($languages)) {
		 $langs = explode(',',$languages);
		 switch($langs[0]) {
		 case 'eng': return 'en';
		 case 'ger':
		 case 'deu': return 'de';
		 case 'hun': return 'hu';
		 case 'fra': return 'fr';
		 case 'dut': return 'nl';
		 default:
         return '';
			logError("Unknown translation to 2-letter code: " . $langs[0]);
		 }
	  } else {
		 return 'en';
	  }
	}

	/************************* ROLE MANAGEMENT **************************************/
	
	/** Retrieves roles and contacts associated with this object */
	function getRoles($language='') {
	  global $db, $vocabularies, $lang, $repository;
	  if(empty($language))
		 $language = $lang;
	  $roles = $db->getAll("SELECT id, contact_id, role_id FROM sotf_object_roles WHERE object_id='$this->id' ORDER BY role_id, contact_id");
	  for($i=0; $i<count($roles); $i++) {
		 $cobj = & $repository->getObject($roles[$i]['contact_id']);
		 if($cobj) {
			$roles[$i]['role_name'] = $vocabularies->getRoleName($roles[$i]['role_id'], $language);
			$roles[$i]['creator'] = $vocabularies->isCreator($roles[$i]['role_id']);
			$roles[$i]['contact_data'] = $cobj->getAllWithIcon();
			if(hasPerm($roles[$i]['contact_id'], 'change')) {
			  $roles[$i]['change_contact'] = 1;
			}
		 } else {
			logError("Referred contact does not exist: " . $roles[$i]['contact_id']);
			unset($roles[$i]);
		 }
	  }
	  return $roles;
	}

	/** Retrieves roles and contacts associated with this object */
	function getCreators() {
		global $db;

		$creators = $db->getAssoc("SELECT c.* FROM sotf_contacts c, sotf_object_roles o, sotf_roles r WHERE c.id = o.contact_id AND  o.role_id=r.role_id AND r.creator='t' AND o.object_id='$this->id' ORDER BY c.name", false, null, DB_FETCHMODE_ASSOC, false);
		return $creators;
	}

	function getCreatorNames() {
	  $creators = $this->getCreators();
	  $first = true;
	  foreach($creators as $creator) {
		 if($first)
			$first = false;
		 else 
			$names .= ', ';
		 $names .= $creator['name'];
	  }
	  return $names;
	}

	/** Static: finds the id for a given role (if exists). */
	function findRole($objectId, $contactId, $roleId) {
		global $db;

		$id = $db->getOne("SELECT id FROM sotf_object_roles WHERE object_id='$objectId' AND contact_id='$contactId' AND role_id='$roleId' ");
		return $id;
	}

	/** Adds a new role/contact to the object. */
	function addRole($contactId, $roleId) {
		$ro = new sotfNodeObject("sotf_object_roles");
		$ro->set('contact_id', $contactId);
		$ro->set('role_id', $roleId);
		$ro->create();
		return $ro->id;
	}

	/** Changes an existing role/contact pair. */
	function changeRole($id, $contactId, $roleId) {
		$ro = new sotfNodeObject("sotf_object_roles", $id);
		$ro->set('contact_id', $contactId);
		$ro->set('role_id', $roleId);
		$ro->update();
	}

	//********************** ICON management ***********************************

	/** Gets icon of the thing. Returns binary string containing the logo. */
	function getIcon()
	{
		return sotf_Blob::findBlob($this->id, 'icon');
	}

	/** Deletes icon of the thing */
	function deleteIcon() {
	  if(!$this->isLocal())
		 raiseError("operation_for_local_objects_only");
	  sotf_Blob::saveBlob($this->id, 'icon','');
	  $iconFile = $this->getMetaDir() . '/icon.png';
	  if(is_readable($iconFile)) {
		 if(!unlink($iconFile))
			addError("Could not delete icon file!");
	  }
	}

	/**
	* Sets icon for object.
	*
	* @param	object	$file	pathname of file
	* @return	boolean	True if the function succeeded, else false
	*/
	function setIcon($file)
	{
		global $config;
		$tmpfile = $config['tmpDir'].'/'.time().".png";
		$succ = $this->prepareIcon($file, $tmpfile, $config['iconWidth'], $config['iconHeight']);
		if (!$succ) {
			addError("Could not resize image");
			//return false;
		} else {
		  if ($fp = fopen($tmpfile,'rb')) {
			 $data = fread($fp,filesize($tmpfile));
			 fclose($fp);
			 // save into DB
			 sotf_Blob::saveBlob($this->id, "icon", $data);
			 // save into file system
			 $iconFile = $this->getMetaDir() . '/icon.png';
			 sotf_Utils::save($iconFile, $data);
		  } else
			 addError("could not open icon file!");
		}
		if(is_file($tmpfile)) {
		  debug("remove tmpfile", $tmpfile);
		  unlink($tmpfile);
		}
		sotf_Blob::uncacheIcon($this->id);
		return true;
	} // end func setIcon


	/** Resizes the given image 'imgfile', converts it into PNG and puts it into 'newfile'. */
	function prepareIcon($imgfile, $newfile, $iconWidth = 100, $iconHeight = 100) {
		global $config;
		if ($imgfile == "") { 
			raiseError("No image file specified");
			return false;
		}
		if (!file_exists($imgfile)) {
			raiseError("File does not exist: $imgfile");
			return false;
		}
	
		//$info = GetAllFileInfo($file->getPath());
		//if (($info['png']['width'] == $iconWidth) && ($info['png']['height'] == $iconHeight))

		debug("imgfile", $imgfile);
		$currentimagesize = getimagesize($imgfile);
		if(!$currentimagesize || ($currentimagesize[0]==0 && $currentimagesize[1]==0)) {
			addError("not_an_image");
			return false;
		}
		$image_width = $currentimagesize[0];
		$image_height= $currentimagesize[1];
		$sizefactor = 1;

		// TODO: convert to PNG!!
		if(($image_height == $iconHeight) && ($image_width == $iconWidth)) {
			if(!copy($imgfile, $newfile))
				raiseError("Could not copy image file");
			return true;
		}

		if (($image_height > $iconHeight) || ($image_width > $iconWidth)) 
		{		
			$sizefactor = min((double)($iconHeight / $image_height), (double)($iconWidth / $image_width));
		}		 
		$newwidth = (int) ($image_width * $sizefactor);
		$newheight = (int) ($image_height * $sizefactor); 

		$newsize = $newwidth . "x" . $newheight;

		debug("resizing image", $newsize);

		$cmd = '"' . $config['magickDir'] . "/convert\" $imgfile -resize $newsize $newfile 2>&1";			

		debug("resize command", $cmd);

		exec($cmd, $exec_output, $exec_retval);

		/* 
		print($cmd);
		if($exec_retval > 0)
			print "ERROR: exec() error: $exec_output[0]";
		else
			print "Image was resized from ".$image_width."x".$image_height." to $newsize :)";
		*/

	return true;
	}

	//********************** JINGLE management ***********************************


	/**
	* Sets jingle of the station.
	* @use	$config['audioFormats']
	*/
	function setJingle($filename, $copy=false) {
		global $config, $page;

		$source = $filename;
		if(!is_file($source))
			raiseError("no such file: $source");
		$srcFile = new sotf_AudioFile($source);
		$target = $this->getMetaDir() .	'/jingle_' . $srcFile->getFormatFilename();
		debug("jingle file", $target);
		if($srcFile->type != 'audio')
			raiseError("this is not an audio file");
		if(is_file($target)) {
			raiseError($page->getlocalized('format_already_present'));
		}
		if($copy)
			$success = copy($source,$target);
		else
			$success = rename($source,$target);
		if(!$success)
			raiseError("could not copy/move $source");
		return true;
		//TODO? save into database
	}

	/**
	* Gets a jingle of the station.
	*
	* @param	integer	$index	Format index of the jingle in the $config['audioFormats'] global variable
	* @return	mixed	Returns the path of the jingle if exist, else return boolean false
	* @use	$config['audioFormats']
	*/
	function getJingle($index = 0) {
	  global $config;
	  
	  $file = $this->getMetaDir() . '/jingle_' . sotf_AudioCheck::getFormatFilename($index);
	  debug("searching for", $file);
	  
	  if (is_file($file) && !is_file($file.'.lock')) {
		 return $file;
	  }
	  
	  $file = '';
	  $jdir = $this->getMetaDir();
	  if(is_dir($jdir)) {
		  $d = dir($jdir);
		  while($entry = $d->read()) {
			 if (substr($entry, 0, 6) == 'jingle_') {
				$file = $jdir . '/' . $entry;
				break;
			 }
		  }
		  $d->close();
	}
	  
	  debug("2nd round", $file);

	  if($file)
		 return $file;
	  else
		 return false;
	}

	/** Deletes a jingle */
	function deleteJingle($file, $index='') {

		if(!preg_match("/^jingle/", $file))
			raiseError("Invalid filename");
		$file = sotf_Utils::getFileInDir($this->getMetaDir(), $file);
		debug("delete file", $file);
		if(!unlink($file)) {
			addError("Could not delete jingle $index!");
		}
		// TODO: delete from SQL???
	}


}
