<?php 
// -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

/**
* 
*
* @author Andras Micsik - micsik@sztaki.hu
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
  
  /************************* ROLE MANAGEMENT **************************************/
  
  /** Retrieves roles and contacts associated with this object */
  function getRoles() {
    $roles = $this->db->getAll("SELECT id, contact_id, role_id FROM sotf_object_roles WHERE object_id='$this->id' ORDER BY role_id, contact_id");
    for($i=0; $i<count($roles); $i++) {
      $roles[$i]['role_name'] = $this->repository->getRoleName($roles[$i]['role_id']);
      $cobj = new sotf_Contact($roles[$i]['contact_id']);
      $roles[$i]['contact_data'] = $cobj->getAllWithIcon();
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
	* @use	$iconWidth
	* @use	$iconHeight
	*/
	function setIcon($file)
	{
		global $iconWidth,$iconHeight, $tmpdir;
    $tmpfile = $tmpdir.'/'.time().".png";
    if (!$this->prepareIcon($file, $tmpfile, $iconWidth, $iconHeight)) {
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
