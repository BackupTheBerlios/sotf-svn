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

	/** caches icon for object, and adds indicator flag for Smarty templates whether there is an icon */
	function getAllWithIcon() {
		$retval = $this->getAll();
		$retval['icon'] = sotf_Blob::cacheIcon($this->id);
		return $retval;
	}

	/** can be static */
	function getLanguagesLocalized($languages = '') {
	  global $page;
	  if(!$languages)
		 $languages = $this->get('language');
	  $langs = explode(',',$languages);
	  for($i=0; $i<count($langs); $i++) {
		 if($i>0)
			$lstring .= ', ';
		 $lstring .= $page->getlocalized($langs[$i]);
	  }
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
		 $langNames[$i] = $page->getlocalized($config['languages'][$i]);
	  }
	  $smarty->assign('LANG_CODES', $config['languages']);
	  $smarty->assign('LANG_NAMES', $langNames);
	  $langs = explode(',',$this->get('language'));
	  $smarty->assign('PRG_LANG1', $langs[0]);
	  $smarty->assign('PRG_LANG2', $langs[1]);
	  $smarty->assign('PRG_LANG3', $langs[2]);
	}

	/************************* ROLE MANAGEMENT **************************************/
	
	/** Retrieves roles and contacts associated with this object */
	function getRoles() {
		global $db, $repository;

		$roles = $db->getAll("SELECT id, contact_id, role_id FROM sotf_object_roles WHERE object_id='$this->id' ORDER BY role_id, contact_id");
		for($i=0; $i<count($roles); $i++) {
			$roles[$i]['role_name'] = $repository->getRoleName($roles[$i]['role_id']);
			$cobj = new sotf_Contact($roles[$i]['contact_id']);
			$roles[$i]['contact_data'] = $cobj->getAllWithIcon();
			if(hasPerm($roles[$i]['contact_id'], 'change')) {
				$roles[$i]['change_contact'] = 1;
			}
		}
		return $roles;
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

	/**
	* Gets icon of the thing
	*
	* @return	string	Binary data contains the logo
	* @use	$db
	*/
	function getIcon()
	{
		return sotf_Blob::findBlob($this->id, 'icon');
	} // end func getIcon

	/**
	* Deletes icon of the thing
	*/
	function deleteIcon()
	{
		sotf_Blob::saveBlob($this->id, 'icon','');
	}

	/**
	* Sets icon for object
	*
	* @param	object	$file	pathname of file
	* @return	boolean	True if the function succeeded, else false
	* @use	$db
	* @use	$config['iconWidth']
	* @use	$config['iconHeight']
	*/
	function setIcon($file)
	{
		global $config;
		$tmpfile = $config['tmpDir'].'/'.time().".png";
		if (!$this->prepareIcon($file, $tmpfile, $config['iconWidth'], $config['iconHeight'])) {
			raiseError("Could not resize image");
			//return false;
		} else {
			if ($fp = fopen($tmpfile,'rb')) {
				$data = fread($fp,filesize($tmpfile));
				fclose($fp);
				// save into DB
				sotf_Blob::saveBlob($this->id, "icon", $data);
			} else
				raiseError("could not open icon file!");
		}
		if(is_file($tmpfile)) {
			debug("tmpfile", $tmpfile);
			//unlink($tmpfile);
		}
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

	  /** THis could be an abstract method, implemented in sotf_Station and sotf_Series */
	  //function getJingleDir() {
	  //function getDir()

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
		$target = $this->getJingleDir() .	'/jingle_' . $srcFile->getFormatFilename();
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
	function getJingle($index = 1)
	{
		global $config;

		$file = $this->getJingleDir() . '/jingle_' . sotf_AudioCheck::getFormatFilename($index);
		debug("searching for", $file);

		if (is_file($file) && !is_file($file.'.lock'))
		{
			return $file;
		}
		else
		{
			return false;
			//return new PEAR_Error($stationId . " has no jingle!");
		}
	}

	/** Deletes a jingle */
	function deleteJingle($file, $index='') {

		if(!preg_match("/^jingle/", $file))
			raiseError("Invalid filename");
		$file = sotf_Utils::getFileInDir($this->getJingleDir(), $file);
		debug("delete file", $file);
		if(!unlink($file)) {
			addError("Could not delete jingle $index!");
		}
		// TODO: delete from SQL???
	}


}
