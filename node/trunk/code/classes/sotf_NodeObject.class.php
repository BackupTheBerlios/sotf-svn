<?php 

/*  -*- tab-width: 3; indent-tabs-mode: 1; -*-
 * $Id$
 *
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

/**
* Objects that are replicated in the network
*/
class sotf_NodeObject extends sotf_Object {

  /** Contains the administrative fields needed for replication. */
  var $internalData;

  /** constructor
   * @return (void)
   */
  function sotf_NodeObject($tablename, $id='', $data='') {
    $this->sotf_Object($tablename, $id, $data);
  }

  /** Generates the ID for a new persistent object. */
  function generateID() {
    $localId = $this->db->nextId($this->tablename);
    $id = sprintf("%03d%2s%d", $this->nodeId, $this->repository->getTableCode($this->tablename), $localId);
    debug("generated ID", $id);
	 return $id;
  }

  /** Creates a new persistent replicated object */
  function create() {
	 global $nodeId, $sotfVars;
   if(empty($this->id)) {
     $this->id = $this->generateID();
     $this->adminObject->id = $this->id;
   }
   $this->internalData['node_id'] = $nodeId;
   $this->internalData['arrived_stamp'] = $sotfVars->get('sync_stamp', 0);
   $this->internalData['arrived'] = $this->db->getTimestampTz();
   $this->internalData['last_change'] = $this->db->getTimestampTz();
   $this->internalData['change_stamp'] = 0;
   $internalObj = new sotf_Object('sotf_node_objects', $this->id, $this->internalData);
   $internalObj->create();
	 $success = parent::create();
   if(!$success) {
     $internalObj->delete();
     //$this->db->query("DELETE FROM sotf_node_objects WHERE id='$this->id'");
   }
   return $success;
  }

  /** Updates the fields of the object. */
  function update() {
    global $sotfVars;
    parent::update();
    $this->internalData = $this->db->getRow("SELECT * FROM sotf_node_objects WHERE id='$this->id' ");
    $this->internalData['arrived_stamp'] = $sotfVars->get('sync_stamp', 0);
    $this->internalData['arrived'] = $this->db->getTimestampTz();
    $this->internalData['last_change'] = $this->db->getTimestampTz();
    $this->internalData['change_stamp']++;
    $internalObj = new sotf_Object('sotf_node_objects', $this->internalData['id'], $this->internalData);
    $internalObj->update();
  }

  /** Deletes the object. */
  function delete() {
	 $this->db->query("DELETE FROM sotf_node_objects WHERE id='" . $this->id . "'");
   // propagate deletion to other nodes
   $this->createDeletionRecord();
	 //parent::delete();  // not needed because of cascading delete
   // delete user permissions
   $this->db->query("DELETE FROM sotf_user_permissions WHERE object_id='$this->id'");
  }

  /** Creates a deletion record: used when a replicated object is deleted. */
  function createDeletionRecord() {
	 $dr = new sotf_NodeObject('sotf_deletions');
	 $dr->set('del_id', $this->id);
	 $dr->create();
  }

  /**************************************************
   *
   *                SYNC SUPPORT
   *
   **************************************************/

  /** Private! Compares a replicated object to the local one and saves it if it's newer than the local. */
	function saveReplica() {
    global $sotfVars;
    $oldData = $this->db->getRow("SELECT * FROM sotf_node_objects WHERE id='$this->id' ");
    //debug("changed", $changed);
    //debug("lch", $this->lastChange);
    if(count($oldData) > 0) {
      if($this->internalData['change_stamp'] && 
         $this->internalData['change_stamp'] > $oldData['change_stamp']) {
        // this is newer, save it
        sotf_Object::update();
        // save internal data
        $this->internalData['arrived_stamp'] = $sotfVars->get('sync_stamp', 0);
        $this->internalData['arrived'] = $this->db->getTimestampTz();
        $internalObj = new sotf_Object('sotf_node_objects', $this->internalData['id'], $this->internalData);
        $internalObj->update();
        // save binary fields
        /*
        reset($this->binaryFields);
        while(list(,$field)=each($this->binaryFields)) {
          sotf_Object::setBlob($field, $this->db->unescape_bytea($this->data[$field]));
        }
        */
        debug("updated ", $this->id);
        return true;
      } else {
        debug("arrived older version of", $this->id);
        return false;
      }
    } else {
      $this->internalData['node_id'] = $nodeId;
      $this->internalData['arrived_stamp'] = $sotfVars->get('sync_stamp', 0);
      $this->internalData['arrived'] = $this->db->getTimestampTz();
      $internalObj = new sotf_Object('sotf_node_objects', $this->id, $this->internalData);
      $internalObj->create();
      $success = sotf_Object::create();
      if(!$success) {
        $internalObj->delete();
      }
      debug("created ", $this->id);
      return $success;
    }
	}

  /** Static: count the objects to be sent to the neighbour node. */
  function countModifiedObjects($remoteNode, $syncStamp = 0) {
    return $db->getOne("SELECT count(*) FROM sotf_node_objects WHERE node_id != '$remoteNode' AND arrived_stamp >= '$syncStamp'");
  }

  /** Static: collects the objects to send to the neighbour node. */
  function getModifiedObjects($remoteNode, $syncStamp = 0, $from, $objectsPerPage, $updatedObjects = array()) {
    global $db, $nodeId, $repository;
    // an ordering in which objects should be retrieved because of foreign keys
    $tableOrder = "no,co,st,se,pr,ri,ed,of,mf,li,td,tt,to,pt,ge,ro,rn,sr,de,ra,re,sx";
    // select objects to send to neighbour
    $result = $db->limitQuery("SELECT * FROM sotf_node_objects WHERE node_id != '$remoteNode' AND arrived_stamp >= '$syncStamp' ORDER BY strpos('$tableOrder', substring(id, 4, 2)), id", $from, $objectsPerPage);
    while (DB_OK === $res->fetchInto($row)) {
      $objects1[] = $row;
    }
    //debug("OBJECTS1", $objects1);
    // collect object data for selected objects
    $objects = array();
    while(list(,$obj) = each($objects1)) {
      // don't send back the same object
      if(!in_array($obj['id'], $updatedObjects)) {   
        $tablename = $repository->getTable($obj['id']);
        $data = $db->getRow("SELECT * FROM $tablename WHERE id = '" . $obj['id'] . "'");
        // don't send occasional empty records
        if(count($data) > 1) {         
          $obj['data'] = $data;
          $objects[] = $obj;
          debug("sending modified object", $obj['id']);
        }
      }
    }
    //debug("OBJECTS__2", $objects);
    return $objects;
  }

  /** Static: saves the objects received from a neighbour node. */
  function saveModifiedObjects($objects) {
    global $repository;
    $updatedObjects = array();
    if(count($objects) > 0) {
      reset($objects);
      while(list(,$objData) = each($objects)) {
        debug("saving modified object", $objData['id']);
        $obj = $repository->getObject($objData['id'], $objData['data']);
        unset($objData['data']);
        $obj->internalData = $objData;
        reset($obj->data);
        /*
        // url decoding and else
        while(list($k,$v) = each($obj->data)) {
          $obj->data[$k] = urldecode($v);
        }
        */
        if($obj->saveReplica()) {
          $updatedObjects[] = $objData['id'];
          // handle deletions
          if($obj->tablename == 'sotf_deletions') {
            $delId = $obj->get('del_id');
            debug("deleting object", $delId);
            $obj = $repository->getObject($delId);
            if($obj)
              $obj->delete();
          }
        }
      }
    }
    return $updatedObjects;
  }
  

}

?>