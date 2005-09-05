<?php // -*- tab-width: 2; indent-tabs-mode: 1; -*-

/* $Id$
 *
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require_once($config['classdir'] . '/sotf_NodeObject.class.php');
require_once($config['classdir'] . '/sotf_ComplexNodeObject.class.php');
require_once($config['classdir'] . '/sotf_Node.class.php');
require_once($config['classdir'] . '/sotf_Neighbour.class.php');
require_once($config['classdir'] . '/sotf_Station.class.php');
require_once($config['classdir'] . '/sotf_Series.class.php');
require_once($config['classdir'] . '/sotf_Programme.class.php');
require_once($config['classdir'] . '/sotf_Contact.class.php');
require_once($config['classdir'] . '/sotf_Rating.class.php');
require_once($config['classdir'] . '/sotf_PlayList.class.php');
require_once($config['classdir'] . '/sotf_UserPlaylist.class.php');
require_once($config['classdir'] . '/sotf_Blob.class.php');

class sotf_Repository {

  /** SQL table codes. */
  var $tableCodes = array( 
									"sotf_nodes" => "no",
									"sotf_contacts" => "co",
									"sotf_stations" => "st",
									"sotf_object_roles" => "sr",
									"sotf_series" => "se",
									"sotf_programmes" => "pr",
									"sotf_rights" => "ri",
									"sotf_extradata" => "ed",
									"sotf_other_files" => "of",
									"sotf_media_files" => "mf",
									"sotf_links" => "li",
									"sotf_topic_trees" => "tt",
									"sotf_topic_tree_defs" => "td",
									"sotf_topics" => "to",
									"sotf_prog_topics" => "pt",
									"sotf_genres" => "ge",
									"sotf_roles" => "ro",
									"sotf_role_names" => "rn",
									"sotf_deletions" => "de",
									"sotf_prog_rating" => "ra",
									"sotf_prog_refs" => "re",
									"sotf_prog_stats" => "sx",
									"sotf_blobs" => "bl",
									"sotf_portals" => "po"
									);

  /** Mapping of table codes into class names. */
  var $codeToClass = array( 
									"co" => "sotf_Contact",
									"st" => "sotf_Station",
									"se" => "sotf_Series",
									"pr" => "sotf_Programme",
									"bl" => "sotf_Blob"
									);

  /** The order in which to send table data to neighbour nodes. */
  var $tableOrder = "de,no,st,co,se,pr,ri,ed,of,mf,li,td,tt,to,pt,ge,ro,rn,sr,bl,ra,re,sx,po";

  var $rootdir;
  var $db;

  /** An internal cache for speeding up object retreival. */
  var $objectCache = array();

  function sotf_Repository($rootDir, $db) {
    $this->rootdir = $rootDir;
    $this->db = $db;
  }

  function getTableCode($tablename) {
	 $tc = $this->tableCodes[$tablename];
	 if(!$tc)
		raiseError("no table code for table $tablename");
	 return $tc;
  }

  function getTable($objectId) {
    $tc = substr($objectId, 3,2);
    return array_search($tc, $this->tableCodes);
  }

  function &getObjectNoCache($objectId, $data='') {
	 // load it from db
    $tc = substr($objectId, 3,2);
    $class = $this->codeToClass[$tc];
    if($class) {
      $obj = new $class($objectId, $data);
    } else {
      $table = array_search($tc, $this->tableCodes);
			if($table)
				$obj = new sotf_NodeObject($table, $objectId, $data);
			else {
				//return NULL;
				raiseError('Invalid id, stop this', $objectId);
			}
    }
    if(!$obj->exists()) {
			debug("Object does not exist",$objectId);
      return NULL;
	 }
	 return $obj;
  }
  
  function &getObject($objectId, $data='') {
		if(empty($objectId)) {
			debug("Invalid object id (empty)");
			return NULL;
		}
		if(!$this->looksLikeId($objectId)) {
			debug("Invalid object id", $objectId);
			return NULL;
		}
		// get from cache if possible
		$object = & $this->getFromCache($objectId);
		if(is_object($object))
			return $object;
		$obj = & $this->getObjectNoCache($objectId, $data);
		//debug("OBJ", $obj);
		if($obj)
			$this->putInCache($obj);
		return $obj;
  }

	function looksLikeId($id) {
		return preg_match('/^[0-9]{3}[a-z]{2}[0-9]+$/', $id);
	}

  function getNodeId($objectId) {
	 if(!preg_match('/\d{3}[a-z]{2}\d+/', $objectId))
		raiseError("invalid object id: $id");
	 return (int)substr($objectId, 0, 3);
  }

  /************************************************
   *      OBJECT CACHING
   ************************************************/

  function &getFromCache($id) {
	 if(is_object($this->objectCache[$id])) {
		debug("CACHE HIT for: $id");
		return $this->objectCache[$id];
	 }
	 return NULL;
  }

  function putInCache(&$object) {
	 $id = $object->getID();
	 if(!$id) {
		//debug("BAD object", $object);
		$object->debug();
		raiseError("Can't cache objects without id");
	 }
	 debug("CACHED: $id");
	 $this->objectCache[$id] = &$object;
  }

  /************************************************
   *      PORTALS
   ************************************************/

  function processPortalEvent($event) {
		debug("processing event", $event);
		$progId = $event['prog_id'];
		if($progId) {
      if($this->looksLikeId($progId))
				$prg = &$this->getObject($progId);
      if(!$prg) {
				debug("Invalid prog_id arrived in portal event", $progId);
				return -1;
      }
		}
    switch($event['name']) {
    case 'programme_added':
      $obj = new sotf_NodeObject('sotf_prog_refs');
      $obj->set('prog_id', $event['value']);
      $obj->set('url', $event['url']);
      $obj->find();
			$obj->set('station_id', $prg->get('station_id'));
      $obj->set('start_date', $event['timestamp']);
      $obj->set('portal_name', $event['portal_name']);
      $obj->save();
      break;
    case 'programme_deleted':
      $obj = new sotf_NodeObject('sotf_prog_refs');
      $obj->set('prog_id', $event['value']);
      $obj->set('url', $event['url']);
      $obj->find();
      if(!$obj->exists()) {
        debug("unknown prog ref arrives: "  . $event['value'] . ' - ' . $event['url']);
        $obj->set('portal_name', $event['portal_name']);
      }
			$obj->set('station_id', $prg->get('station_id'));
      $obj->set('end_date', $event['timestamp']);
      //$obj->set('portal_name', $event['portal_name']);
      $obj->save();
      break;
    case 'visit':
      $obj = new sotf_NodeObject('sotf_prog_refs');
      $obj->set('prog_id', $event['value']['prog_id']);
      $obj->set('url', $event['url']);
      $obj->find();
      if(!$obj->exists()) {
				// TODO: how can this happen? It happens too many times!
        debug("unknown prog ref arrives: " . $event['value']['prog_id'] . ' - ' . $event['url']);
        $obj->set('start_date', $event['timestamp']);
        $obj->set('portal_name', $event['portal_name']);
      }
			$obj->set('station_id', $prg->get('station_id'));
      $obj->set('visits', (int)$obj->get('visits')+1);
      // TODO: count unique accesses
      $obj->save();
      break;
    case 'page_impression':
      $obj = new sotf_NodeObject('sotf_portals');
      $obj->set('url', $event['url']);
      $obj->find();
      $obj->set('name', $event['portal_name']);
      $obj->set('page_impression', $event['value']);
      $obj->set('last_access', $event['timestamp']);
      $obj->save();
      break;
    case 'portal_updated':
      $obj = new sotf_NodeObject('sotf_portals');
      $obj->set('url', $event['url']);
      $obj->find();
      $obj->set('name', $event['portal_name']);
      $obj->set('last_update', $event['timestamp']);
      $obj->save();
      break;
    case 'users':
      $obj = new sotf_NodeObject('sotf_portals');
      $obj->set('url', $event['url']);
      $obj->find();
			if(!$obj->exists()) {
				$obj->set('name', $event['portal_name']);
			}
      $obj->set('last_update', $event['timestamp']);
      $obj->set('reg_users', $event['value']);
			if(!$obj->get('name') || !$obj->get('url'))
				logError("Bad portal even teceived", implode(" | ", $event));
			else
				$obj->save();
      break;
    case 'rating':
		// first save in prog_refs
      $obj = new sotf_NodeObject('sotf_prog_refs');
      $obj->set('prog_id', $event['value']['prog_id']);
      $obj->set('url', $event['url']);
      $obj->find();
      if(!$obj->exists()) {
        debug("unknown prog ref arrives: " . $event['url']);
        $obj->set('start_date', $event['timestamp']);
        $obj->set('portal_name', $event['portal_name']);
      }
			$obj->set('station_id', $prg->get('station_id'));
      $obj->set('rating', $event['value']['RATING_VALUE']);
      $obj->set('raters', $event['value']['RATING_COUNT']);
      $obj->save();
		// TODO second, put into global rating database
		/*
		$rating = new sotf_Rating();
		$id = $event['value']['prog_id'];
		$obj = & $this->getObject($id);
		if($obj->isLocal()) {
		  $data = $event['value'];
		  $rating->setRemoteRating($data);
		} else {
		  logError("received rating for non-local object!");
		}
		*/
		break;
	 case 'comment':
		// first save in prog_refs
      $obj = new sotf_NodeObject('sotf_prog_refs');
      $obj->set('prog_id', $event['value']['prog_id']);
      $obj->set('url', $event['url']);
      $obj->find();
      if(!$obj->exists()) {
        logError("unknown prog ref arrives: " . $event['value']['prog_id'] . ' - ' . $event['url']);
        $obj->set('start_date', $event['timestamp']);
        $obj->set('portal_name', $event['portal_name']);
      }
			$obj->set('station_id', $prg->get('station_id'));
      $obj->set('comments', (int)$obj->get('comments')+1);
      $obj->save();
		// save comment
      $obj = new sotf_Object('sotf_comments');
      $obj->set('prog_id', $event['value']['prog_id']);
      $obj->set('portal', $event['url']);
      $obj->set('entered', $event['timestamp']);
      $obj->set('comment_title', $event['value']['title']);
      $obj->set('comment_text', $event['value']['comment']);
      $obj->set('from_name', $event['value']['user_name']);
      $obj->set('from_email', $event['value']['email']);
		$obj->create();
		// TODO forward to authors
		break;
    case 'query_added':
      //debug("query from portal", $event);
    case 'query_deleted':
    case 'file_uploaded':
      // silently ignored
      break;
    default:
      logError("unknown portal event: " . $event['name']);
    }
  }

  /************************************************
   *      MAINTENANCE
	 *
	 * This is rarely needed, but with some old postgres, cascading deletes may have problems.
	 * Not complete, partial solution, to be finished.
   ************************************************/

	function cleanTables($test = false) {

		$this->cleanForeignKey('sotf_blobs', 'id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_blobs', 'object_id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_nodes', 'id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_user_permissions', 'permission_id', 'sotf_permissions', 'id');

		$this->cleanForeignKey('sotf_stations', 'id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_contacts', 'id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_contacts', 'station_id', 'sotf_stations', 'id');

		$this->cleanForeignKey('sotf_series', 'id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_series', 'station_id', 'sotf_stations', 'id');

		$this->cleanForeignKey('sotf_object_roles', 'id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_object_roles', 'contact_id', 'sotf_contacts', 'id');

		$this->cleanForeignKey('sotf_object_roles', 'object_id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_programmes', 'id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_programmes', 'station_id', 'sotf_stations', 'id');

		//// series field is not compulsory! 
		//// $this->cleanForeignKey('sotf_programmes', 'series_id', 'sotf_series', 'id');

		$this->cleanForeignKey('sotf_rights', 'id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_rights', 'prog_id', 'sotf_programmes', 'id');

		$this->cleanForeignKey('sotf_extradata', 'id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_extradata', 'prog_id', 'sotf_programmes', 'id');

		$this->cleanForeignKey('sotf_other_files', 'id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_other_files', 'prog_id', 'sotf_programmes', 'id');

		$this->cleanForeignKey('sotf_media_files', 'id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_media_files', 'prog_id', 'sotf_programmes', 'id');

		$this->cleanForeignKey('sotf_links', 'id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_links', 'prog_id', 'sotf_programmes', 'id');

		$this->cleanForeignKey('sotf_topic_tree_defs', 'id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_topic_trees', 'id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_topics', 'id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_topics', 'topic_id', 'sotf_topic_tree_defs', 'id');

		$this->cleanForeignKey('sotf_prog_topics', 'id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_prog_topics', 'prog_id', 'sotf_programmes', 'id');

		$this->cleanForeignKey('sotf_genres', 'id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_roles', 'id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_role_names', 'id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_role_names', 'role_id', 'sotf_roles', 'role_id');

		$this->cleanForeignKey('sotf_deletions', 'id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_playlists', 'prog_id', 'sotf_programmes', 'id');

		$this->cleanForeignKey('sotf_ratings', 'prog_id', 'sotf_programmes', 'id');

		$this->cleanForeignKey('sotf_prog_rating', 'id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_prog_rating', 'prog_id', 'sotf_programmes', 'id');

		$this->cleanForeignKey('sotf_prog_refs', 'id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_prog_refs', 'prog_id', 'sotf_programmes', 'id');

		$this->cleanForeignKey('sotf_prog_stats', 'id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_prog_stats', 'prog_id', 'sotf_programmes', 'id');
		
		$this->cleanForeignKey('sotf_prog_stats', 'station_id', 'sotf_stations', 'id');

		$this->cleanForeignKey('sotf_stats', 'prog_id', 'sotf_programmes', 'id');

		$this->cleanForeignKey('sotf_stats', 'station_id', 'sotf_stations', 'id');

		$this->cleanForeignKey('sotf_comments', 'prog_id', 'sotf_programmes', 'id');

		$this->cleanForeignKey('sotf_to_forward', 'prog_id', 'sotf_programmes', 'id');

		$this->cleanForeignKey('sotf_unique_access', 'prog_id', 'sotf_programmes', 'id');

		$this->cleanForeignKey('sotf_user_progs', 'prog_id', 'sotf_programmes', 'id');

		$this->cleanForeignKey('sotf_portals', 'id', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_station_mappings', 'id_at_node', 'sotf_node_objects', 'id');

		$this->cleanForeignKey('sotf_station_mappings', 'station', 'sotf_stations', 'id');

	}

	function cleanForeignKey($table1, $id1, $table2, $id2) {
		$data = $this->db->getAll("select r.* from $table1 r left join $table2 p on (r.$id1=p.$id2) where p.$id2 is null");
		$this->cleanOrphans($table1, $data, $id1, $test);
	}

	function cleanOrphans($table, $rows, $ref, $test) {
		while(list(,$row) = each($rows)) {
			//debug("ROW", $row);
			$id = $row['id'];
			$refId = $row[$ref];
			//debug("REF", "'".$refId."'");
			$delete = false;
			if($table=='sotf_contacts' && $ref=='station_id') {
				global $db;
				$count = $db->getOne("SELECT count(*) from sotf_object_roles WHERE contact_id = '$id'");
				if($count > 0) {
					logError("Strange! Contact is still used.", $id);
					continue;
				}
				$delete = true;
			}
			elseif(empty($refId)) {
				logError("Empty reference in $id to ", $refId);
				//$delete = true;
				continue;
			}
			elseif(!$this->looksLikeId($refId)) {
				logError("Invalid reference in $id to ", $refId);
				$delete = true;
				//continue;
			} else {
				$obj = &$this->getObjectNoCache($refId);
				if(!$obj)
					$delete = true;
			}
			if($delete) {
				echo "<div>DELETING $id</div>\n";
				logError("Database inconsistency ($id), please add constraints.sql!","DELETE FROM $table WHERE id='$id'");
				if(!$test)
					$this->db->query("DELETE FROM $table WHERE id='$id'");
			} else {
				logError("$id cannot be deleted: $refId still exists?");
			}
		}
	}

}

?>
