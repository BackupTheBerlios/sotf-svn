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
      $roles[$i]['contact_data'] = $cobj->getAll();
    }
    return $roles;
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

  /** this places the icon into the www/tmp, so that you can refer to it with <img src=
  function cacheIcon() {
    global $cachedir, $cacheprefix;
    $fname = "$cachedir/" . $this->id;
    if(is_readable($fname))
  }
  */

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
    $tmpfile = $tmpdir.'/'.time().".img";
    if (!sotf_Utils::resizeImage($file, $tmpfile, $iconWidth, $iconHeight)) {
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




}
