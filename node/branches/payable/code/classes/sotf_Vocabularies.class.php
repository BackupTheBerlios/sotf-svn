<?php // -*- tab-width: 2; indent-tabs-mode: 1; -*-

/* $Id$
 *
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */


class sotf_Vocabularies {

	var $db;

  var $roles;

  var $genres;

  /************************************************
   *      TOPICS
   ************************************************/

	function sotf_Vocabularies(&$db) {
    $this->db = &$db;
  }

  function getTopicName($topicId, $lang='') {
		if(!$lang)
			$lang = $GLOBALS['lang'];
    if(!empty($topicId)) {
      $db = $this->db;
      $name = $db->getOne("SELECT topic_name FROM sotf_topics WHERE topic_id='$topicId' AND language='$lang'");
			if(!$name)
				$name = '????';
      $tid = $db->getOne("SELECT supertopic FROM sotf_topic_tree_defs WHERE id='$topicId'");
      //debug("tid", "X${tid}X");
      while($tid != '0' && $tid) {
        $n1 = $db->getOne("SELECT topic_name FROM sotf_topics WHERE topic_id='$tid' AND language='$lang'");
        $tid = $db->getOne("SELECT supertopic FROM sotf_topic_tree_defs WHERE id='$tid'");
				if($tid)
					$name = $n1 . ' / ' . $name;
				//debug("tid", "X${tid}X");
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
		/*
	 if(!$topicId || $this->getTable($topicId) != 'sotf_topic_tree_defs') {
		logError("invalid topic: $topicId");
		return;
	 }
		*/
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
		$this->db->begin(true);
		$disappeared = $this->db->getCol("SELECT t.id FROM sotf_prog_topics t LEFT JOIN sotf_programmes p ON t.prog_id=p.id WHERE p.id IS NULL");
		foreach($disappeared as $did) {
			$this->db->query("DELETE FROM sotf_prog_topics WHERE id='$did'");
		}
    $this->db->query("DELETE FROM sotf_topics_counter");
    $this->db->query("SELECT setval('sotf_topics_counter_id_seq', 1, false)");
    $this->db->query("INSERT INTO sotf_topics_counter (id, topic_id, number, total) SELECT nextval('sotf_topics_counter_id_seq'), t.id, count(p.id), NULL::int FROM sotf_topic_tree_defs t LEFT JOIN sotf_prog_topics p ON t.id = p.topic_id GROUP BY t.id");
	
	$this->db->query("UPDATE sotf_topics_counter SET number=0 WHERE topic_id=(SELECT c.topic_id FROM sotf_topics_counter c INNER JOIN sotf_prog_topics p ON c.topic_id = p.topic_id INNER JOIN sotf_programmes pr ON p.prog_id = pr.id WHERE pr.type='video')"); //ADDED BY Martin Schmidt
	
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
	 //debug("R", $r);
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
    //debug('supertopics', $supertopics);
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
	 //$sql = "SELECT p.*, s.name as station, se.name as serietitle FROM sotf_programmes p LEFT JOIN sotf_stations s ON p.station_id = s.id LEFT JOIN sotf_series se ON p.series_id = se.id, sotf_prog_topics t WHERE p.published = 't' AND p.id = t.prog_id AND t.topic_id = '$topicId'";
	 $sql = "SELECT p.*, s.name as station, se.name as serietitle FROM sotf_programmes p LEFT JOIN sotf_stations s ON p.station_id = s.id LEFT JOIN sotf_series se ON p.series_id = se.id, sotf_prog_topics t WHERE p.published = 't' AND p.id = t.prog_id AND t.topic_id = '$topicId' AND p.type='sound'"; // ADDED BY Martin Schmidt
	 if(!$start) $start = 0;
	 $res = $this->db->limitQuery($sql, $start, $hitsPerPage);
	 if(DB::isError($res))
		raiseError($res);
    while (DB_OK === $res->fetchInto($item)) {
		$list[] = $item;
	 }
	 return $list;
  }

  function importTopicTree($lines, $language) {

		debug("START import topic tree", $language);

		// read in topic tree definition
		reset($lines);
		$more = true;
		while($more) {
			$line = array_shift($lines);
			if(preg_match('/^\s*$/', $line) || preg_match('/^#/', $line)) {
				$more = false;
			} else {
				if(preg_match('/^\s*([\w_]+)\s*=\s*(.*)/', $line, $items)) {
					$treedata[$items[1]] = trim($items[2]);
				} else {
					logError("Bad line: $line");
				}
			}
		}
		debug("tree data", $treedata);
		if(!$treedata['tree_id'] || !$treedata['name'])
			raiseError("bad topic tree definition");
		
		$treeId = $treedata['tree_id'];
		
		// create tree def
		$td = & new sotf_NodeObject("sotf_topic_trees");
		$td->setID(sotf_NodeObject::makeId($treeId, $td->tablename, 0));
		$td->find();
		$td->set('tree_id', $treeId);
		$td->set('name', $treedata['shortname']);
		if($td->exists()) {
			$langs = $td->get('languages');
			if(strpos($langs, $language) === FALSE) {
				$td->set('languages', $langs . ",$language");
				$td->update();
			}
		} else {
			$td->set('languages', $language);
			$td->create();
		}
		
		// create root description
		$x = new sotf_NodeObject("sotf_topic_tree_defs");
		$x->setID(sotf_NodeObject::makeId($treeId, $x->tablename, 0));
		$x->find();
		if(!$x->exists()) {
			$x->set('supertopic', 0);
			$x->set('name', $treedata['name']);
			$x->set('tree_id', $treeId);
			$x->create();
		}
		$rootId = $x->getID();
		
		// create root translation
		$y = new sotf_NodeObject("sotf_topics");
		$y->setID(sotf_NodeObject::makeId($treeId, $y->tablename,  '0' . $language));
		$y->find();
		if(!$y->exists()) {
			$y->set('topic_id', $rootId);
			$y->set('language', $language);
			$y->set('topic_name', $treedata['name']);
			$y->set('description', $treedata['description']);
			$y->create();
		}
		
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
			$name = trim($items[3]);
			if($level < $l) {
				$roots[] = $parentId;
				$parentId = $prevId;
			}
			while($level > $l) {
				$parentId = array_pop($roots);
				$level--;
			}
			$level = $l;
			debug("", "LEVEL: $level, PARENT: $parentId, ROOTS: " . join(",", $roots));
			$x = new sotf_NodeObject("sotf_topic_tree_defs");
			$x->setID(sotf_NodeObject::makeId($treeId, $x->tablename, $id));
			$x->find();
			if(!$x->exists()) {
				$x->set('supertopic', $parentId);
				$x->set('name', $name);
				$x->set('tree_id', $treeId);
				$x->create();
			}
			$tid = $x->getID();
			$y = new sotf_NodeObject("sotf_topics");
			$y->setID(sotf_NodeObject::makeId($treeId, $y->tablename, $id . $language));
			//$y->find();
			$y->set('topic_id', $tid);
			$y->set('language', $language);
			$y->set('topic_name', $name);
			$y->create();
			$prevId = $tid;
		}

		debug("END import topic tree", $language);

  }
  
  /************************************************
   *      ROLES
   ************************************************/

	/** Imports role translations from a text files into database */
	function importRoles($lines, $language) {
		if(empty($lines)) {
			logError("importRoles: file is empty");
			return;
		}
		reset($lines);
		while(list(,$line) = each($lines)) {
			if(preg_match('/^\s*$/', $line) || preg_match('/^#/', $line))
				continue;
			if(!preg_match('/^\s*(\d+)\s+(.*)/', $line, $items)) {
				logError("bad line syntax: $line");
				continue;
			}
			debug("role item", $items);
			$id = $items[1];
			$name = trim($items[2]);

			$oid = sotf_NodeObject::makeId(0, "sotf_roles", $id);
			$o1 = new sotf_NodeObject("sotf_roles", $oid);
			if(!$o1->exists()) {
				$o1->set('role_id', $id);
				$o1->set('creator', 'f');
				$o1->create();
			}
			$oid = sotf_NodeObject::makeId(0, "sotf_role_names", $id . $language);
			$o2 = new sotf_NodeObject("sotf_role_names", $oid);
			$o2->set('role_id', $id);
			$o2->set('language', $language);
			$o2->set('name', $name);
			$o2->create();
		}
	}

	// load roles
	function loadRoles($language='') {
		global $lang, $db;
		if(empty($language))
			$language = $lang;
		if(empty($this->roles[$language])) {
			$this->roles[$language] = $db->getAll("SELECT rn.role_id AS id, rn.name, ro.creator FROM sotf_roles ro, sotf_role_names rn WHERE ro.role_id=rn.role_id AND rn.language='$language'");
		}
		//debug("ROLES", $this->roles);
	}

  function getRoleName($id, $language='') {
		if(empty($language))
			$language = $GLOBALS['lang'];
		$this->loadRoles($language);
    reset($this->roles[$language]);
    while(list(,$r) = each($this->roles[$language])) {
      if($r['id']==$id)
        return $r['name'];
    }
    debug("unknown role id", $id);
    return "UNKNOWN_ROLE";
  }

	function isCreator($id) {
		global $lang;
		$this->loadRoles();
    reset($this->roles[$lang]);
    while(list(,$r) = each($this->roles[$lang])) {
      if($r['id']==$id) {
				if($r['creator'] == 't')
					return true;
				else
					return false;
			}
    }
    debug("unknown role id 2", $id);
    return false;
	}

  function getRoleId($name, $language) {
		$this->loadRoles();
    $name = sotf_Utils::magicQuotes($name);
    $language = sotf_Utils::magicQuotes($language);
    return $this->db->getOne("SELECT role_id FROM sotf_role_names WHERE name='$name' AND language='$language'");
  }

  function getRoles($language='') {
		global $lang;
		if(empty($language))
			$language = $lang;
		$this->loadRoles($language);
		return $this->roles[$language];
  }

  /************************************************
   *      GENRES
   ************************************************/

	/** Imports genre translations from a text files into database */
	function importGenres($lines, $language) {
		if(empty($lines)) {
			logError("importGenres: file is empty");
			return;
		}
		reset($lines);
		while(list(,$line) = each($lines)) {
			if(preg_match('/^\s*$/', $line) || preg_match('/^#/', $line))
				continue;
			if(!preg_match('/^\s*(\d+)\s+(.*)/', $line, $items)) {
				logError("bad line syntax: $line");
				continue;
			}
			debug("genre item", $items);
			$id = $items[1];
			$name = trim($items[2]);

			$o1 = new sotf_NodeObject("sotf_genres");
			$o1->setID($o1->makeId(0, $o1->tablename, $id . $language));
			$o1->set('genre_id', $id);
			$o1->set('language', $language);
			$o1->set('name', $name);
			$o1->create();
		}
	}

	// load genres
	function loadGenres($language='') {
		global $lang, $db;
		if(empty($language))
			$language = $lang;
		if(empty($this->genres[$language])) {
			$this->genres[$language] = $db->getAll("SELECT genre_id AS id, name FROM sotf_genres WHERE language='$language'");
			//debug("genres", $this->genres);
		}
	}

  function getGenres($language='') {
		global $lang;
		if(empty($language))
			$language = $lang;
		$this->loadGenres($language);
		return $this->genres[$language];
  }
	
  function getGenreName($id, $language='') {
		if(empty($language))
			$language = $GLOBALS['lang'];
		$this->loadGenres($language);
    while(list(,$r) = each($this->genres[$language])) {
      if($r['id']==$id) {
				//debug("genre", $r);
        return $r['name'];
			}
    }
    debug("unknown genre id", $id);
    return "UNKNOWN_GENRE";
  }
	
  /************************************************
   *      XML-RPC ACCESS TO CONTROLLED VOCABULARIES
   ************************************************/

  function getCVocabularyNames() {
	 global $sotfVars;
	 // avail langs are stored in sotf_vars and updated by install.php
	 // roles
	 $langs = explode(',', $sotfVars->get('roles_langs', 'eng'));
	 foreach($langs as $l) {
		$retval[] = array("roles","",$l);
	 }
	 // genres
	 $langs = explode(',', $sotfVars->get('genres_langs', 'eng'));
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
		debug("type", $type);
		debug("name", $name);
		debug("language", $language);

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
