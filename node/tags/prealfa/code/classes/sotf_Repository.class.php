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
require_once($classdir . '/sotf_Rating.class.php');
require_once($classdir . '/sotf_PlayList.class.php');
require_once($classdir . '/sotf_Blob.class.php');

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
									"sotf_stats" => "sx",
                  "sotf_blobs" => "bl"
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

  function getObject($objectId, $data='') {
    $tc = substr($objectId, 3,2);
    $class = $this->codeToClass[$tc];
    if($class) {
      $obj = new $class($objectId, $data);
    } else {
      $table = array_search($tc, $this->tableCodes);
      $obj = new sotf_NodeObject($table, $objectId, $data);
    }
    if( count($obj->getAll())==0 )
      return NULL;
    else
      return $obj;
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

  function addToTopic($progId, $topicId) {
    global $db;
    // TODO: ha mar van, akkor ne adja hozza
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
    reset($this->roles);
    while(list(,$r) = each($this->roles)) {
      if($r['id']==$id)
        return $r['name'];
    }
    debug("unkwon role id", $id);
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

}

?>
