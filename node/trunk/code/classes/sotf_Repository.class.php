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
    switch($event['name']) {
    case 'programme_added':
      $obj = new sotf_NodeObject('sotf_prog_refs');
      $obj->set('prog_id', $event['value']);
      $obj->set('url', $event['url']);
      $obj->find();
      if(!$obj->exists()) {
        $prg = &$this->getObject($obj->get('prog_id'));
		  if(!$prg)
			 break;
        $obj->set('station_id', $prg->get('station_id'));
      }
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
        $prg = &$this->getObject($obj->get('prog_id'));
		  if(!$prg)
			 break;
        $obj->set('station_id', $prg->get('station_id'));
        $obj->set('portal_name', $event['portal_name']);
      }
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
        $prg = &$this->getObject($obj->get('prog_id'));
 		  if(!$prg)
			 break;
       $obj->set('station_id', $prg->get('station_id'));
        $obj->set('start_date', $event['timestamp']);
        $obj->set('portal_name', $event['portal_name']);
      }
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
      //$obj->set('name', $event['portal_name']);
      //$obj->set('last_update', $event['timestamp']);
      $obj->set('reg_users', $event['value']);
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
        $prg = &$this->getObject($obj->get('prog_id'));
		  if(!$prg)
			 break;
        $obj->set('station_id', $prg->get('station_id'));
        $obj->set('start_date', $event['timestamp']);
        $obj->set('portal_name', $event['portal_name']);
      }
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
        $prg = &$this->getObject($obj->get('prog_id'));
        $obj->set('station_id', $prg->get('station_id'));
        $obj->set('start_date', $event['timestamp']);
        $obj->set('portal_name', $event['portal_name']);
      }
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

}

?>
