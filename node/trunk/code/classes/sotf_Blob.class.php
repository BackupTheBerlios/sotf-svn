<?php 
// -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

/**
* 
*
* @author Andras Micsik - micsik@sztaki.hu
*/
class sotf_Blob extends sotf_NodeObject {

	function sotf_Blob($id='', $data='') {
    $this->tablename = 'sotf_blobs';
		$this->sotf_NodeObject($this->tablename, $id, $data);
	}

  /** static */
  function findBlob($id, $name) {
    $obj = new sotf_Blob();
    $obj->set('object_id', $id);
    $obj->set('name', $name);
    $obj->find();
    if($obj->exists())
      return $obj->getBlob('data');
    else
      return NULL;
  }
    
  /** static */
  function saveBlob($id, $name, $blob) {
    $obj = new sotf_Blob();
    $obj->set('object_id', $id);
    $obj->set('name', $name);
    $obj->find();
    if($obj->exists()) {
      if($blob) {
        $obj->setBlob('data', $blob);
      } else {
        $obj->delete();
      }
    } else {
      if($blob) {
        $obj->create();
        $obj->setBlob('data', $blob);
      }
      // else nothing to do!
    }
  }

  /** static, this places the icon into the www/tmp, so that you can refer to
      it with <img src=, returns true if there is an icon for this object */
  function cacheIcon($id) {
    global $cachedir;
    $cacheTimeout = 2*60; // 2 minutes
    if(!$id)
      raiseError("missing id");
    $fname = "$cachedir/" . $id . '.png';
    if(is_readable($fname)) {
      $stat = stat($fname);
      if(time() - $stat['mtime'] <= $cacheTimeout)
        return true;
    }
    $icon = sotf_Blob::findBlob($id, 'icon');
    if(!$icon)
      return false;
    // TODO: cache cleanup!
    ////debug("cache: ". filesize($fname) ."==" . strlen($icon));
    if(is_readable($fname) && filesize($fname)==strlen($icon)) {
      return true;
    }
    debug("cached icon for", $id);
    sotf_Utils::save($fname, $icon);
    return true;
  }



}
