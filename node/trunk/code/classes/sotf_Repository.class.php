<?php 
//-*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

require_once($classdir . '/sotf_NodeObject.class.php');
require_once($classdir . '/sotf_ComplexNodeObject.class.php');
require_once($classdir . '/sotf_Node.class.php');
require_once($classdir . '/sotf_Neighbour.class.php');
require_once($classdir . '/sotf_Station.class.php');
require_once($classdir . '/sotf_Series.class.php');
require_once($classdir . '/sotf_Programme.class.php');
require_once($classdir . '/sotf_Contact.class.php');
//require_once($classdir . '/sotf_PList.class.php');
//require_once($classdir . '/sotf_Metadata.class.php');

class sotf_Repository {

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
									"sotf_refs" => "re",
									"sotf_stats" => "sx"
									);

  var $codeToClass = array( 
									"co" => "sotf_Contact",
									"st" => "sotf_Station",
									"se" => "sotf_Series",
									"pr" => "sotf_Programme",
									);

  var $rootdir;
  var $db;

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

  function getObject($objectId) {
    $tc = substr($objectId, 3,2);
    $class = $this->codeToClass[$tc];
    if($class) {
      return new $class($objectId);
    } else {
      $table = array_search($tc, $this->tableCodes);
      return new sotf_NodeObject($table, $objectId);
    }
  }

  //TODO
  function getTopicTree($language) {

  }

  function getTopicName($topicId) {
    global $lang;
    $db = $this->db;
    $name = $db->getOne("SELECT topic_name FROM sotf_topics WHERE topic_id='$topicId' AND language='$lang'");
    $tid = $db->getOne("SELECT supertopic FROM sotf_topic_tree_defs WHERE id='$topicId'");
    while($tid != 0) {
      $n1 = $db->getOne("SELECT topic_name FROM sotf_topics WHERE topic_id='$tid' AND language='$lang'");
      $name = $n1 . ' / ' . $name;
      $tid = $db->getOne("SELECT supertopic FROM sotf_topic_tree_defs WHERE id='$tid'");
    }
    return $name;
  }

  function getTopTopics($maxHits) {
    $res = $this->db->limitQuery("SELECT * FROM sotf_topics_counter WHERE total > 0 ORDER BY total DESC", 0, $maxHits);
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

  function updateTopicCounts() {
    // calculate counts by topic
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
  }

  /** private recursive function to calculate topic totals including subtopics */
  function sumTopics(&$topics, $index) {
    // calculate total for $topics[$index]
    debug("sumTopics", "$index, " . $topics[$index]['id']);
    if(isset($topics[$index]['total'])) {
      // it's already calculated
      debug("mar kesz", $topics[$index]['total']);
      return $topics[$index]['total'];
    }
    $topicId = $topics[$index]['id'];
    $total = $topics[$index]['number'];
    for($i=0; $i<count($topics); $i++) {
      if($topics[$i]['supertopic'] == $topicId)
        $total = $total + $this->sumTopics($topics, $i);
    }
    $topics[$index]['total'] = $total;
    debug("szamitva", $topics[$index]['total']);
    return $topics[$index]['total'];
  }

  function getRoleName($id) {
    while(list(,$r) = each($this->roles)) {
      if($r['id']==$id)
        return $r['name'];
    }
    return "UNKNOWN_ROLE";
  }

  function getRoles() {
		return $this->roles;
  }

  function getGenres() {
	 return $this->genres;
  }

  function getGenreName($id) {
	 return $this->genres[$id];
  }


  ////////////// SYNC support /////////////////////////////////////////////

  /** xmlRpcServernek kell */
  function getAllStationData() {
    $db = $this->db;
    return $db->getAll("SELECT * FROM sotf_stations", DB_FETCHMODE_ASSOC);
  }

  /** returns all *published* metadata after the given timestamp excluding items from the given node */
  function getAllSeries($timestamp, $excludeNode) {
    $db = $this->db;
    if($excludeNode) {
      $sql = "SELECT i.*, s.node FROM sotf_series i, sotf_stations s WHERE i.station=s.station AND s.node != '$excludeNode' ";
      if($timestamp)
	$sql .= " AND i.last_change >= '$timestamp' ";
    } else {
      $sql = "SELECT * FROM sotf_series";
      if($timestamp)
	$sql .= " WHERE last_change >= '$timestamp'";
    }
    return $db->getAll($sql, DB_FETCHMODE_ASSOC);
  }

  /** returns all *published* metadata after the given timestamp excluding items from the given node */
  function getAllIds($timestamp = '') {
    $sql = "SELECT id, last_change FROM sotf_items WHERE published='t'";
    if($timestamp)
      $sql .= " AND last_change >= '$timestamp'";
    return $this->db->getAll($sql, DB_FETCHMODE_ASSOC);
  }

  /** receives a new item or item update, returns the delay of the change getting here */
  function remoteItemUpdate($item) {
    global $nodeId;
    $db = $this->db;
    $id = new Id($item['station'], $item['entry_date'], $item['track']);
    $where = $id->whereClause();
    $where2 = $id->whereClause2('i');
    // get list of db fields
    $tableInfo = $db->tableInfo('sotf_items', DB_TABLEINFO_ORDER);
    $fields = $tableInfo['order'];
    // TODO: needs synchronization if two different requests try to insert the same thing parallelly
    $existing = $db->getRow("SELECT i.*, s.node FROM sotf_items i, sotf_stations s WHERE i.station=s.station AND $where2");
    if(empty($existing)) {
      foreach($item as $key => $value) {
	if($value && array_key_exists($key, $fields)) {
	  $value = addslashes(stripslashes($value));
	  $sql1 .= "$key, ";
	  $sql2 .= " '$value', ";
	}
      }
      $sql1 = substr($sql1, 0, strlen($sql1)-2); // chop trailing comma
      $sql2 = substr($sql2, 0, strlen($sql2)-2); // chop trailing comma
      $sql = "INSERT INTO sotf_items ( $sql1 ) VALUES ( $sql2 )";
      $res = $db->query($sql);
      if(DB::isError($res)) {
	die ($res->getMessage());
	//return -1;
      }
    } else {
      // need UPDATE instead of INSERT
      if($existing['node'] == $nodeId)
	return 0;
      $t1 = strtotime($item['last_change']);
      $t2 = strtotime($existing['last_change']);
      if($t1 <= $t2)
	return 0;
      $sql = "UPDATE sotf_items SET ";
      foreach($item as $key => $value) {
	if($value && array_key_exists($key, $fields) && $key != 'station' && $key != 'entry_date' && $key != 'track' && $key != 'id') {
	  $value = addslashes(stripslashes($value));
	  $sql .= " $key='$value', ";
	}
      }
      $sql = substr($sql, 0, strlen($sql)-2); // chop trailing comma
      $sql = $sql . " WHERE $where";
      $res = $db->query($sql);
      if(DB::isError($res)) {
	die ($res->getMessage());
	//return -1;
      }
      $updated = true;
    }
    debug("item changed?", $item);
    $t1 = strtotime($item['last_change']);
    $now = time();
    return $now - $t1;
  }

  function deleteRemoteItem($idString) {
    $id = Id::parseId($idString);
    $this->db->query("DELETE FROM sotf_items WHERE " . $id->whereClause());
  }

  function deleteRemoteStation($id) {
     $this->db->query("DELETE FROM sotf_stations WHERE station='$id'");
     $this->db->query("DELETE FROM sotf_items WHERE station='$id'");
  }

  function deleteRemoteSeries($id) {
     $this->db->query("DELETE FROM sotf_series WHERE id='$id'");
  }

  function localUpdates($since) {
    global $nodeId;
    return $this->db->getAll("SELECT i.* FROM sotf_items i, sotf_stations s WHERE i.station=s.station AND s.node='$nodeId' AND i.last_change > '$since' AND i.published='t'");
  }

  function localSeries($since) {
    global $nodeId;
    return $this->db->getAll("SELECT i.* FROM sotf_series i, sotf_stations s WHERE i.station=s.station AND s.node='$nodeId' AND i.last_change > '$since'");
  }

  /////////// end SYNC support ////////////////////////////////////////////////

}

?>
