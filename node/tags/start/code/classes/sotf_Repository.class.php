<?php //-*- tab-width: 3; indent-tabs-mode: 1; -*-

require($classdir . '/sotf_Base.class.php');
require($classdir . '/sotf_Node.class.php');
require($classdir . '/sotf_Neighbour.class.php');
require($classdir . '/sotf_Station.class.php');
require($classdir . '/sotf_Series.class.php');
require($classdir . '/sotf_Programme.class.php');
require($classdir . '/sotf_PrgList.class.php');
require($classdir . '/sotf_Metadata.class.php');

class sotf_Repository {

  var $rootdir;
  var $db;

  function sotf_Repository($rootDir, $db) {
    $this->rootdir = $rootDir;
    $this->db = $db;
  }

  /*

  function getStation($id) {
    return new sotf_Station($id);
  }

  function getSeries($id) {
    return new sotf_Series($id);
  }
  */

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


    function simpleSearch($text, $language, $from, $count) {
      $db = $this->db;
      $sql = "SELECT * FROM sotf_programmes WHERE published='t' ";
      $sql .= " AND (title ~* '$text' OR keywords ~* '$text' OR abstract ~* '$text' OR author ~* '$text' OR spatial_coverage ~* '$text') ";
      if($language && $language != 'any_language') {
        $language = sotf_Utils::clean($language);
        $sql .= " AND language='$language' ";
      }
      $sql .= " ORDER BY production_date DESC ";
      $res = $db->limitQuery($sql, $from, $count);
      if(DB::isError($res))
        raiseError($res->getMessage());
      while (DB_OK === $res->fetchInto($row)) {
        debug("row", $row['title']);
        $list[] = new sotf_Programme($row['id'], $row);
      }
      return $list;
    }

  function search($station, $author, $word, $from, $until, $language) {
	$sql = "SELECT * FROM sotf_programmes";
	
	if($station) {
	  if(array_search(getlocalized("every_station"), $station)===NULL) {
	    for($stationcount = 0; $stationcount < count($station); $stationcount++) {
	      if($stationcount != 0)
	        $sqlstation = $sqlstation." OR station='".clean($station[$stationcount])."'";
          else
            $sqlstation = " (station='".clean($station[0])."'";
        }
	    $sqlstation = $sqlstation.") ";
	    $where[] = $sqlstation;
	  }
	  else {
	    unset($station);
	    unset($sqlstation);
	  }
	}

	if($language) {
	  if(array_search(getlocalized("any_language"), $language)===NULL) {
		for($langcount = 0; $langcount < count($language); $langcount++) {
		  if($langcount != 0)
			$sqllang = $sqllang." OR language='".clean($language[$langcount])."'";
		  else
			$sqllang = " (language='".clean($language[0])."'";
		}
		$sqllang = $sqllang . ") ";
		$where[] = $sqllang;
	  }
	}

	if($author) {
	  $where[] = " author ~* '.*" . clean($author) . ".*' ";
	}
	if($word) {
	  $like = " '.*" . clean($word) . ".*' ";
	  $where[] = " (area ~* $like OR title ~* $like OR keywords ~* $like OR abstract ~* $like) ";
	}
	if($from) {
	  $where[] = " production_date >= '" . clean($from) . "' ";
	}
	if($until) {
	  $where[] = " production_date <= '" . clean($until) . "' ";
	}
	$where[] = " published='t' ";
//	var_dump($where);
    if($where)
	  $sql .= " WHERE ".join(" AND ", $where);
	return $this->db->getAll($sql, DB_FETCHMODE_ASSOC );
  }

}

?>
