<?php 
// -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

/**
* 
*
* @author Andras Micsik - micsik@sztaki.hu
*/
class sotf_ComplexNodeObject extends sotf_NodeObject {

	function sotf_ComplexNodeObject($tablename, $id='', $data='') {
		//debug("constructor", 'sotf_ComplexNodeObject');
		$this->sotf_NodeObject($tablename, $id, $data);
	}						

  function getRoles() {
    $roles = $this->db->getAll("SELECT id, contact_id, role_id FROM sotf_object_roles WHERE object_id='$this->id' ORDER BY role_id, contact_id");
    for($i=0; $i<count($roles); $i++) {
      $roles[$i]['role_name'] = $this->repository->getRoleName($roles[$i]['role_id']);
      $cobj = new sotf_Contact($roles[$i]['contact_id']);
      $cobj->cacheIcon();
      $roles[$i]['contact_data'] = $cobj->getAll();
    }
    return $roles;
  }

  /** static */
  function findRole($objectId, $contactId, $roleId) {
    global $db;
    $id = $db->getOne("SELECT id FROM sotf_object_roles WHERE object_id='$objectId' AND contact_id='$contactId' AND role_id='$roleId' ");
    return $id;
  }

  function addRole($contactId, $roleId) {
    $ro = new sotfNodeObject("sotf_object_roles");
    $ro->set('contact_id', $contactId);
    $ro->set('role_id', $roleId);
    $ro->create();
    return $ro->id;
  }

  function changeRole($id, $contactId, $roleId) {
    $ro = new sotfNodeObject("sotf_object_roles", $id);
    $ro->set('contact_id', $contactId);
    $ro->set('role_id', $roleId);
    $ro->update();
  }

	/**
	* Gets icon of the thing
	*
	* @return	string	Binary data contains the logo
	* @use	$db
	*/
	function getIcon()
	{
    if(in_array('icon', $this->binaryFields))
      return $this->getBlob("icon");
    else
      return NULL;
	} // end func getIcon

	/**
	* Deletes icon of the thing
	*/
	function deleteIcon()
	{
    if(!in_array('icon', $this->binaryFields))
      raiseError("this object cannot have an icon!");
    $this->setBlob('icon','');
	}

  /** this places the icon into the www/tmp, so that you can refer to it with <img src= */
  function cacheIcon($id = '', $icon = '') {
    global $cachedir, $cacheprefix;
    if(!$id) {
      $id = $this->id;
      $icon = $this->getBlob('icon');
    }
    if(empty($icon))
      return;
    $fname = "$cachedir/" . $id . '.png';
    // TODO: cache cleanup!
    ////debug("cache: ". filesize($fname) ."==" . strlen($icon));
    if(is_readable($fname) && filesize($fname)==strlen($icon)) {
      return;
    }
    debug("cached icon for", $id);
    sotf_Utils::save($fname, $icon);
  }

	/**
	* Sets icon for object
	*
	* @param	object	$file	pathname of file
	* @return	boolean	True if the function succeeded, else false
	* @use	$db
	* @use	$iconWidth
	* @use	$iconHeight
	*/
	function setIcon($file)
	{
		global $iconWidth,$iconHeight, $tmpdir;
    if(!in_array('icon', $this->binaryFields))
      raiseError("this object cannot have an icon!");
    $tmpfile = $tmpdir.'/'.time().".png";
    if (!$this->prepareIcon($file, $tmpfile, $iconWidth, $iconHeight)) {
      raiseError("Could not resize image");
      //return false;
    } else {
      if ($fp = fopen($tmpfile,'rb')) {
        $data = fread($fp,filesize($tmpfile));
        fclose($fp);
        // save into DB
        $this->setBlob("icon",$data);
      } else
        raiseError("could not open icon file!");
    }
    if(is_file($tmpfile))
      unlink($tmpfile);
		return true;
	} // end func setIcon


	function prepareIcon($imgfile, $newfile, $iconWidth = 100, $iconHeight = 100) {
		global $magickDir;
		if ($imgfile == "") { 
      raiseError("No image file specified");
      return false;
    }
		if (!file_exists($imgfile)) {
      raiseError("File does not exist: $imgfile");
      return false;
    }
	
		//$info = GetAllMP3info($file->getPath());
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

		$cmd = "\"$magickDir/convert\" $imgfile -resize $newsize $newfile 2>&1";     

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


}
