<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-

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
									"sotf_blobs" => "bl"
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
  var $tableOrder = "no,co,st,se,pr,ri,ed,of,mf,li,td,tt,to,pt,ge,ro,rn,sr,bl,de,ra,re,sx";

  var $rootdir;
  var $db;

  /** An internal cache for speeding up object retreival. */
  var $objectCache = array();

  var $roles;

  var $genres;

  function sotf_Repository($rootDir, $db) {
	 global $lang;
    $this->rootdir = $rootDir;
    $this->db = $db;
	 // load roles
	 $this->roles = $db->getAll("SELECT role_id AS id, name FROM sotf_role_names WHERE language='$lang'");
	 // load genres
	 $this->genres = $db->getAll("SELECT genre_id AS id, name FROM sotf_genres WHERE language='$lang'");
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
      $obj = new sotf_NodeObject($table, $objectId, $data);
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

  /** Tells if the given object id is for one of the global controlled vocabularies (roles, genres, topics). */
  function isVocabularyTable($tablename) {
    $tc = $this->getTableCode($tablename);
    //debug('tc', $tc);
    if($tc == 'tt' || $tc == 'td' || $tc == 'to' || $tc == 'ge' || $tc == 'ro' || $tc == 'rn') {
      debug("vocabulary entry");
      return true;
    } else {
      return false;
    }
  }

  /** Generates the ID for a new persistent object. */
  function generateID($object) {
    global $config;
    if($config['nodeId'] == 0)
      raiseError('Please set config[nodeId] to a positive integer in config.inc.php');
    $tableCode = $this->getTableCode($object->tablename);
    if($this->isVocabularyTable($object->tablename)) 
      $nid = 0;
    else
      $nid = $config['nodeId'];
    $localId = $this->db->nextId($object->tablename);
    $id = sprintf("%03d%2s%d", $nid, $tableCode, $localId);
    debug("generated ID", $id);
    return $id;
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
   *      TOPICS
   ************************************************/

  function getTopicName($topicId) {
    global $lang;
    if($topicId) {
      $db = $this->db;
      $name = $db->getOne("SELECT topic_name FROM sotf_topics WHERE topic_id='$topicId' AND language='$lang'");
      $tid = $db->getOne("SELECT supertopic FROM sotf_topic_tree_defs WHERE id='$topicId'");
      debug("tid", "X${tid}X");
      while($tid != '0') {
        $n1 = $db->getOne("SELECT topic_name FROM sotf_topics WHERE topic_id='$tid' AND language='$lang'");
        $tid = $db->getOne("SELECT supertopic FROM sotf_topic_tree_defs WHERE id='$tid'");
		  if($tid)
			 $name = $n1 . ' / ' . $name;
        debug("tid", "X${tid}X");
      }
    } else {
      $name = '???';
    }
    debug('name', $name);
    return $name;
  }

  function getTopTopics($maxHits) {
    $res = $this->db->limitQuery("SELECT tc.* FROM sotf_topics_counter tc, sotf_topic_tree_defs td WHERE tc.topic_id=td.id AND td.supertopic != 0 AND total > 0 ORDER BY total DESC", 0, $maxHits);
    if(DB::isError($res)) {
      addError($res);
			return array();
    }
    while (DB_OK === $res->fetchInto($item)) {
      $item['name'] = $this->getTopicName($item['topic_id']);
			$list[] = $item;
		}
		return $list;
  }

  function addToTopic($progId, $topicId) {
    global $db;
	 if(!$topicId || $this->getTable($topicId) != 'sotf_topic_tree_defs') {
		logError("invalid topic: $topicId");
		return;
	 }
    $query="SELECT id  FROM sotf_prog_topics WHERE  prog_id = '".$progId."' AND topic_id  = '".$topicId."'";
    $result = $db->getAll($query);
    if (count($result) == 0)		//if not already in the DB
    {
	    $x = new sotf_NodeObject("sotf_prog_topics");
	    $x->set('prog_id', $progId);
	    $x->set('topic_id', $topicId);
	    $x->create();
	
	    $query="UPDATE sotf_topics_counter SET number = number+1 WHERE topic_id = '".$topicId."'";
	    $result = $db->query($query);
	
	    $query="SELECT supertopic FROM sotf_topic_tree_defs WHERE id = '".$topicId."'";
	    $supertopic = $db->getOne($query);
	    if (!$supertopic) $supertopic = $topicId;
	    $query="UPDATE sotf_topics_counter SET total = total+1 WHERE topic_id = '".$supertopic."'";
	    $result = $db->query($query);
    }
  }

  function delFromTopic($id) {
    global $db;
    // TODO: ha mar van, akkor ne adja hozza
    $obj = new sotf_NodeObject('sotf_prog_topics', $id);
    $topicId = $obj->get('topic_id');
    $progId = $obj->get('prog_id');
    $obj->delete();

    $query="UPDATE sotf_topics_counter SET number = number-1 WHERE topic_id = '".$topicId."'";
    $result = $db->query($query);

    $query="SELECT supertopic FROM sotf_topic_tree_defs WHERE id = '".$topicId."'";
    $supertopic = $db->getOne($query);
    if (!$supertopic) $supertopic = $topicId;
    $query="UPDATE sotf_topics_counter SET total = total-1 WHERE topic_id = '".$supertopic."'";
    $result = $db->query($query);
  }

  function updateTopicCounts() {
    // calculate counts by topic
	 $this->db->begin();
    $this->db->query("DROP TABLE sotf_topics_counter");
    $this->db->query("SELECT setval('sotf_topics_counter_id_seq', 1, false)");
    $this->db->query("SELECT nextval('sotf_topics_counter_id_seq') AS id, t.id AS topic_id, count(p.id) AS number, NULL::int AS total INTO sotf_topics_counter FROM sotf_topic_tree_defs t LEFT JOIN sotf_prog_topics p ON t.id = p.topic_id GROUP BY t.id");
    // calculate totals including subtopic counts
    $topics = $this->db->getAll("SELECT t.id, supertopic, number FROM sotf_topic_tree_defs t, sotf_topics_counter c WHERE t.id = c.topic_id ");
    for($i=0; $i<count($topics); $i++) {
      $this->sumTopics($topics, $i);
    }
    for($i=0; $i<count($topics); $i++) {
      if($topics[$i]['total'] != 0)
        $this->db->query("UPDATE sotf_topics_counter SET total='" . $topics[$i]['total'] . "' WHERE topic_id='" . $topics[$i]['id'] . "'");
    }
    $this->db->query("UPDATE sotf_topics_counter SET total=0 WHERE total IS NULL");
	 $this->db->commit();
  }

  /** private recursive function to calculate topic totals including subtopics */
  function sumTopics(&$topics, $index) {
    // calculate total for $topics[$index]
    //debug("sumTopics", "$index, " . $topics[$index]['id']);
    if(isset($topics[$index]['total'])) {
      // it's already calculated
      return $topics[$index]['total'];
    }
    $topicId = $topics[$index]['id'];
    $total = $topics[$index]['number'];
    for($i=0; $i<count($topics); $i++) {
      if($topics[$i]['supertopic'] == $topicId)
        $total = $total + $this->sumTopics($topics, $i);
    }
    $topics[$index]['total'] = $total;
    //debug("calculated", $topics[$index]['total']);
    return $topics[$index]['total'];
  }

  /** collects immediate subtopics for the given topic */
  function getSubTopics($topicId, $language) {
	 $subtopics = $this->db->getAll("SELECT sotf_topics.topic_id AS id, sotf_topics.topic_name AS name, number, total FROM sotf_topic_tree_defs LEFT JOIN sotf_topics ON sotf_topics.topic_id = sotf_topic_tree_defs.id LEFT JOIN sotf_topics_counter ON sotf_topics_counter.topic_id = sotf_topic_tree_defs.id WHERE sotf_topics.language='$language' AND sotf_topic_tree_defs.supertopic='$topicId' ORDER BY sotf_topics.topic_name");
	 return $subtopics;
  }

  function getSuperTopic($topicId, $language) {
	 $r = $this->db->getRow("SELECT supertopic, tree_id FROM sotf_topic_tree_defs WHERE id='$topicId'");
	 debug("R", $r);
	 if(!$r['supertopic']) {
		debug("return null");
		return NULL;
	 } else {
		$sup = $r['supertopic'];
		return $this->db->getRow("SELECT sotf_topics.topic_id AS id, sotf_topics.topic_name AS name FROM sotf_topic_tree_defs LEFT JOIN sotf_topics ON sotf_topics.topic_id = sotf_topic_tree_defs.id WHERE sotf_topics.language='$language' AND sotf_topic_tree_defs.id='$sup'");
	 }
  }

  function getDefaultTreeId() {
	 return 1;
  }

  function getTopicTreeRoot($treeId = '') {
	 if(!$treeId)
		$treeId = $this->getDefaultTreeId();
	 return $this->db->getOne("SELECT id FROM sotf_topic_tree_defs WHERE tree_id='$treeId' AND supertopic=0");
  }

  function getTopicInfo($topicId, $language) {
		return $this->db->getRow("SELECT td.*, tn.topic_name AS name FROM sotf_topic_tree_defs td LEFT JOIN sotf_topics tn ON tn.topic_id = td.id WHERE tn.language='$language' AND td.id='$topicId'");
  }

  function getTopicTreeInfo($treeId, $language) {
		return $this->db->getRow("SELECT sotf_topics.*, tt.* FROM sotf_topic_tree_defs LEFT JOIN sotf_topics ON sotf_topics.topic_id = sotf_topic_tree_defs.id LEFT JOIN sotf_topic_trees tt ON sotf_topic_tree_defs.tree_id=tt.tree_id WHERE sotf_topics.language='$language' AND sotf_topic_tree_defs.tree_id='$treeId' AND sotf_topic_tree_defs.supertopic=0");
  }

  function listTopicTrees($language) {
	 return $this->db->getAll("SELECT sotf_topics.*, tt.* FROM sotf_topic_tree_defs LEFT JOIN sotf_topics ON sotf_topics.topic_id = sotf_topic_tree_defs.id LEFT JOIN sotf_topic_trees tt ON sotf_topic_tree_defs.tree_id=tt.tree_id WHERE sotf_topics.language='$language' AND sotf_topic_tree_defs.supertopic=0");
  }

  function listTrees() {
	 return $this->db->getAll("SELECT * FROM sotf_topic_trees");
  }

  /** return a topic tree */
  function getTree($treeId, $language, $withCounts = false) {
    $supertopics = $this->db->getCol("SELECT DISTINCT supertopic FROM sotf_topic_tree_defs WHERE tree_id='$treeId'");
    debug('supertopics', $supertopics);
	 $rootId = $this->db->getOne("SELECT id FROM sotf_topic_tree_defs WHERE tree_id='$treeId' AND supertopic=0");
	 if(!$rootId || DB::isError($rootId))
		raiseError("no such topic tree: $treeId");
    return $this->dumpTree($rootId, 0, $treeId, $language, $supertopics, $withCounts);
  }

  /** private recursive function for dumping trees */
  function dumpTree($root, $level, $treeId, $language, $supertopics, $withCounts = false) {
    if($withCounts) {
      $rawlist = $this->db->getAll("SELECT sotf_topics.topic_id AS id, sotf_topics.topic_name AS name, supertopic, number, total FROM sotf_topic_tree_defs LEFT JOIN sotf_topics ON sotf_topics.topic_id = sotf_topic_tree_defs.id LEFT JOIN sotf_topics_counter ON sotf_topics_counter.topic_id = sotf_topic_tree_defs.id WHERE sotf_topics.language='$language' AND sotf_topic_tree_defs.supertopic='$root' ORDER BY sotf_topics.topic_name");
    } else {
      $rawlist = $this->db->getAll("SELECT sotf_topics.topic_id AS id, sotf_topics.topic_name AS name, supertopic FROM sotf_topic_tree_defs LEFT JOIN sotf_topics ON sotf_topics.topic_id = sotf_topic_tree_defs.id WHERE sotf_topics.language='$language' AND sotf_topic_tree_defs.supertopic='$root' ORDER BY sotf_topics.topic_name");
    }
    while(list(,$a) = each($rawlist)) {
      $a['level'] = $level;
      $list[] = $a;
      if(in_array($a['id'], $supertopics)) {
        $list = array_merge($list, $this->dumpTree($a['id'], $level+1, $treeId, $language, $supertopics, $withCounts));
      }
    }
    return $list;
  }

  function countProgsForTopic($topicId) {
	 return $this->db->getOne("SELECT count(*) FROM sotf_programmes p, sotf_prog_topics t WHERE p.published = 't' AND p.id = t.prog_id AND t.topic_id = '$topicId'");
  }

  function getProgsForTopic($topicId, $start, $hitsPerPage) {
	 $sql = "SELECT p.*, s.name as station, se.name as serietitle FROM sotf_programmes p LEFT JOIN sotf_stations s ON p.station_id = s.id LEFT JOIN sotf_series se ON p.series_id = se.id, sotf_prog_topics t WHERE p.published = 't' AND p.id = t.prog_id AND t.topic_id = '$topicId'";
	 if(!$start) $start = 0;
	 $res = $this->db->limitQuery($sql, $start, $hitsPerPage);
	 if(DB::isError($res))
		raiseError($res);
    while (DB_OK === $res->fetchInto($item)) {
		$list[] = $item;
	 }
	 return $list;
  }

  

  function importTopicTree($treedata, $lines) {
	 
	 $treeId = $treedata['tree_id'];

	 // create tree def
	 $o1 = & new sotf_NodeObject("sotf_topic_trees");
	 $o1->set('tree_id', $treeId);
	 $o1->set('name', $treedata['shortname']);
	 $o1->set('languages', 'en');
	 $o1->create();

	 // create root description
	 $x = new sotf_NodeObject("sotf_topic_tree_defs");
	 $x->set('supertopic', 0);
	 $x->set('name', $treedata['name']);
	 $x->set('tree_id', $treeId);
	 $x->create();
	 $rootId = $x->getID();
	 // create root translation
	 $y = new sotf_NodeObject("sotf_topics");
	 $y->set('topic_id', $rootId);
	 $y->set('language', 'en');
	 $y->set('topic_name', $treedata['name']);
	 $y->set('description', $treedata['description']);
	 $y->create();
	 
	 $parentId = $rootId;
	 $prevId = $rootId;
	 $level = 0;
	 
	 reset($lines);
	 while(list(,$line) = each($lines)) {
		if(preg_match('/^\s*$/', $line) || preg_match('/^#/', $line))
		  continue;
		if(!preg_match('/^\s*(\d+)\s+(\d+)\s+(.*)/', $line, $items)) {
		  logError("bad line syntax: $line");
		  continue;
		}
		//$items = preg_split('/\s+/', $line, PREG_SPLIT_NO_EMPTY);
		debug("tree items", $items);
		$id = $items[1];
		$l = $items[2];
		$name = $items[3];
		if($level < $l) {
		  $roots[] = $parentId;
		  $parentId = $prevId;
		}
		if($level > $l) {
		  $parentId = array_pop($roots);
		}
		$level = $l;
		debug("", "LEVEL: $level, PARENT: $parentId, ROOTS: " . join(",", $roots));
		$x = new sotf_NodeObject("sotf_topic_tree_defs");
		$x->set('supertopic', $parentId);
		$x->set('name', $name);
		$x->set('tree_id', $treeId);
		$x->create();
		$id = $x->getID();
		$y = new sotf_NodeObject("sotf_topics");
		$y->set('topic_id', $id);
		$y->set('language', 'en');
		$y->set('topic_name', $name);
		$y->create();
		$prevId = $id;
	 }
  }
  
  /************************************************
   *      ROLES
   ************************************************/

  function getRoleName($id) {
    reset($this->roles);
    while(list(,$r) = each($this->roles)) {
      if($r['id']==$id)
        return $r['name'];
    }
    debug("unkwon role id", $id);
    return "UNKNOWN_ROLE";
  }

  function getRoleId($name, $language) {
    $name = sotf_Utils::magicQuotes($name);
    $language = sotf_Utils::magicQuotes($language);
    return $this->db->getOne("SELECT role_id FROM sotf_role_names WHERE name='$name' AND language='$language'");
  }

  function getRoles() {
		return $this->roles;
  }

  /************************************************
   *      GENRES
   ************************************************/

  function getGenres() {
	 return $this->genres;
  }

  function getGenreName($id) {
	 return $this->genres[$id-1];
  }

  /************************************************
   *      XML-RPC ACCESS TO CONTROLLED VOCABULARIES
   ************************************************/

  function getCVocabularyNames() {
	 global $sotfVars;
	 // avail langs are stored in sotf_vars and refreshed by cron
	 // roles
	 $langs = explode(',', $sotfVars->get('roles_langs', 'en'));
	 foreach($langs as $l) {
		$retval[] = array("roles","",$l);
	 }
	 // genres
	 $langs = explode(',', $sotfVars->get('genres_langs', 'en'));
	 foreach($langs as $l) {
		$retval[] = array("genres","",$l);
	 }
	 // subject trees
	 $trees = $this->listTrees();
	 foreach($trees as $t) {
		$langs = explode(',', $t['languages']);
		foreach($langs as $l) {
		  $retval[] = array("topics", $t['tree_id'], $l);
		}
	 }
	 return $retval;
  }

  /** type=(topics,roles,genres) */
  function getCVocabulary($type, $name, $language) {
    if($type=='topics') {
      $retval = $this->getTree($name, $language);
    } elseif($type=='roles') {
      $retval = $this->db->getAll("SELECT role_id AS id, name FROM sotf_role_names WHERE language='$language'");
    } elseif($type=='genres') {
      $retval = $this->db->getAll("SELECT genre_id AS id, name FROM sotf_genres WHERE language='$language'");
    } else {
      logError("Unknown getCVocabulary request type: $type");
      return "Unknown getCVocabulary request type: $type";
    }
    if(DB::isError($retval)) {
      return "DB error";
    }
    return $retval;
  }

}

?>
