<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-

/* 
 * $Id$
 *
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *				at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

define("STAT_VISIT", 1);
define("STAT_LISTEN", 2);
define("STAT_DOWNLOAD", 3);

class sotf_Statistics extends sotf_Object {

  var $tablename = 'sotf_stats';

  function sotf_Statistics($id='', $data='') {
    $this->sotf_Object('sotf_stats', $id, $data);
  }

  /** static */
  function getGlobalStats($prgId) {
    global $db;
    $result = $db->getRow("SELECT * FROM sotf_prog_stats WHERE prog_id='$prgId'");
    if(DB::isError($result))
      return array('visits'=>0, 'listens'=> 0, 'downloads' => 0, 	
                   "unique_listens" => 0, "unique_downloads" => 0, "unique_visits" => 0, "detail" => '');
    else {
      debug("STATS", $result);
      return $result;
    }
  }

  /** static */
  function networkStats() {
    global $db;
    return $db->getRow("SELECT sum(visits) AS visits, sum(listens) AS listens, sum(downloads) AS downloads FROM sotf_prog_stats");
    //, sum(unique_visits) AS unique_visits, sum(unique_listens) AS unique_listens, sum(unique_downloads) AS unique_downloads
  }

  /** static */
  function addStat($prgId, $fileId, $type) {
    global $db, $repository;
    
    if($type != 'listens' && $type != 'downloads' && $type != 'visits')
      raiseError("addStat: type should be 'listens' or 'downloads' or 'visits'");

    // update periodic stat
    $now = getdate();
    $year = $now['year'];
    $month = $now['mon'];
    $day = $now['mday'];
    $week = date('W');
    $where = " WHERE prog_id='$this->id' AND year='$year' AND month='$month' AND day='$day' AND week='$week'";
		$id = $db->getOne("SELECT id FROM sotf_stats $where");
		if($id) {
		  $obj = new sotf_Statistics($id);
		  $obj->set($type, $obj->get($type)+1);
		} else {
		  $obj = new sotf_Statistics();
      $prg = $repository->getObject($prgId);
      if(!$prg)
        raiseError("addStat: no such programme: $prgId");
		  $obj->setAll(array('prog_id' => $prg->id,
                         'station_id' => $prg->get('station_id'),
                         'year' => $year,
                         'month' => $month,
                         'week' => $week,
                         'day' => $day,
                         $type => 1));
		}

    // update uniqueness memory
    sotf_Statistics::addUniqueAccess(getHostName(), $prgId, $fileId, $type);

    // would be too often: 
    $obj->updateUniqueStats();
    $obj->updatePrgStats();
    $obj->save();
  }

  /** static */
  function addUniqueAccess($ip, $prgId, $fileId, $type) {
    global $db;
    $convert = array('visits'=>'100',
                     'listens'=>'010',
                     'listens'=>'001');
    $subIdValue = empty($fileId) ? 'IS NULL' : "='$fileId'" ;
    $db->query("UPDATE sotf_unique_access SET action = action | B'" . $convert[$type] . "' WHERE prog_id='$prgId' AND sub_id $subIdValue AND ip='$ip'");
    if($db->affectedRows()==0) {
      $subIdValue = empty($fileId) ? 'NULL' : "'$fileId'" ;
      $db->query("INSERT INTO sotf_unique_access (prog_id, sub_id, ip, action) VALUES('$prgId', $subIdValue, '$ip', B'$convert[$type]')");
    }
  }

  function updateUniqueStats() {
    global $db;
    // calculate unique 
    $prgId = $this->get('prog_id');
    $uVisits = $db->getOne("SELECT count(distinct ip) FROM sotf_unique_access WHERE prog_id='$prgId' AND SUBSTRING(action from 1 for 1)=B'1'");
    $this->set('unique_visits', $uVisits);
    $uListens = $db->getOne("SELECT count(distinct ip) FROM sotf_unique_access WHERE prog_id='$prgId' AND SUBSTRING(action from 2 for 1)=B'1'");
    $this->set('unique_listens', $uListens);
    $uDownloads = $db->getOne("SELECT count(distinct ip) FROM sotf_unique_access WHERE prog_id='$prgId' AND SUBSTRING(action from 3 for 1)=B'1'");
    $this->set('unique_downloads', $uDownloads);
  }

  function updatePrgStats() {
    global $db, $repository;
    $id = $db->getOne("SELECT id FROM sotf_prog_stats WHERE prog_id='" . $this->get('prog_id') . "'");
    if(!$id) {
      $obj = new sotf_NodeObject("sotf_prog_stats");
      $obj->set('prog_id', $this->get('prog_id'));
      $obj->set('station_id', $this->get('station_id'));
    } else {
      $obj = $repository->getObject($id);
    }
    $obj->set('unique_visits', $this->get('unique_visits'));
    $obj->set('unique_listens', $this->get('unique_listens'));
    $obj->set('unique_downloads', $this->get('unique_downloads'));
    $prgId = $this->get('prog_id');
    $data = $db->getRow("SELECT SUM(visits) as visits, SUM(listens) as listens, SUM(downloads) as downloads FROM sotf_stats WHERE prog_id='$prgId'"); 
    $obj->set('visits', $data['visits']);
    $obj->set('listens', $data['listens']);
    $obj->set('downloads', $data['downloads']);
    $obj->save();
  }


}

?>