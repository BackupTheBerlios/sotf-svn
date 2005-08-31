<?php 
// -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

/**
* @author Andras Micsik - micsik@sztaki.hu
*/
class sotf_Blob extends sotf_NodeObject {

  /** constructor */
	function sotf_Blob($id='', $data='') {
    $this->tablename = 'sotf_blobs';
    $this->binaryFields = array('data');
		$this->sotf_NodeObject($this->tablename, $id, $data);
	}

  function hasBlob($id, $name) {
    $obj = new sotf_Blob();
    $obj->set('object_id', $id);
    $obj->set('name', $name);
    $obj->find();
    if($obj->exists()) {
      return true;
    } else
      return false;
  }

  /** Static: returns the blob with the given name for the given object ('id'). */
  function findBlob($id, $name) {
    $obj = new sotf_Blob();
    $obj->set('object_id', $id);
    $obj->set('name', $name);
    $obj->find();
    if($obj->exists()) {
      return $obj->get('data');
    } else
      return NULL;
  }
    
  /** Static: saves the blob for the given object ('id') under the given name. */
  function saveBlob($id, $name, $blob) {
    $obj = new sotf_Blob();
    //debug("saving blob", substr($blob, 0, 40));
    $obj->set('object_id', $id);
    $obj->set('name', $name);
    $obj->find();
    $obj->set('data', $blob);
    if($obj->exists()) {
      if($blob) {
        $obj->update();
      } else {
        $obj->delete();
      }
    } else {
      if($blob) {
        $obj->create();
      }
      // else nothing to do!
    }
  }

  function cacheIcon2($data) {
    global $repository;
    //debug('cacheIcon2', $data);
    $found = sotf_Blob::cacheIcon($data['id']);
    if($found)
      return $data['id'] . '.png';
    if($data['series_id'])
      $found = sotf_Blob::cacheIcon($data['series_id']);
    if($found)
      return $data['series_id'] . '.png';
    if($data['station_id'])
      $found = sotf_Blob::cacheIcon($data['station_id']);
    if($found)
      return $data['station_id'] . '.png';
    return false;
  }

  /** static, this places the icon into the www/tmp, so that you can refer to
      it with <img src=, returns true if there is an icon for this object */
  function cacheIcon($id) {
    global $config;
    $cacheTimeout = 10*60; // 10 minutes
    if(!$id)
      raiseError("missing id");
    $fname = $config['cacheDir'] . "/" . $id . '.png';
    if(is_readable($fname)) {
      $stat = stat($fname);
      if(time() - $stat['mtime'] <= $cacheTimeout)
        return true;
      else {
	debug("cached icon of $id expired");
	if(!unlink($fname)) {
	  clearstatcache();
	  if(file_exists($fname))
	    logError("Could not delete $fname");
	}
      }
    }
    $icon = sotf_Blob::findBlob($id, 'icon');
    if(!$icon)
      return false;
    debug("cached icon of $id for ", $forId);
    sotf_Utils::save($fname, $icon);
    return true;
  }

  function uncacheIcon($id) {
    global $config;
    if(!$id)
      raiseError("missing id");
    $fname = $config['cacheDir'] . "/" . $id . '.png';
    if(is_file($fname)) {
      if(!unlink($fname))
        logError("Could not uncache icon for $id!");
    }
  }

}
