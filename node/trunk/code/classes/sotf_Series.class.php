<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-

/* 
 * $Id$
 *
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *				at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

/**
* Models a series
*
* @author Andras Micsik SZTAKI DSD micsik@sztaki.hu
*/
class sotf_Series extends sotf_ComplexNodeObject {		

   /**
     * Constructor: loads the object from database if ids are given
     *
     * @param string tablename name of SQL table to store
     * @param string node node id
     * @param string id id within node
   */
  function sotf_Series($id='', $data='') {
	global $db;

    $this->sotf_ComplexNodeObject('sotf_series', $id, $data);
    if($id) {
      //$this->stationName = $db->getOne("SELECT name FROM sotf_stations WHERE id='" . $this->get('station_id') . "'");
    }
  }

  function getStation() {
    return new sotf_Station($this->get('station_id'));
  }

  /** get number of published programmes */
  function numProgrammes($onlyPublished = true) {
	global $db;

    $sql = "SELECT COUNT(*) FROM sotf_programmes WHERE series_id='$this->id'";
    if($onlyPublished)
      $sql .= " AND published='t'";
    $count = $db->getOne($sql);
    if (DB::isError($count))
      raiseError($count->getMessage());
    else
      return $count;
  }

	/** list programmes */
	function listProgrammes($start, $hitsPerPage, $onlyPublished = true) {
	global $db;

		$id = $this->id;
		$sql = "SELECT * FROM sotf_programmes WHERE series_id = '$id' ";
		if($onlyPublished)
			$sql .= " AND published='t' ";
		$sql .= " ORDER BY entry_date DESC,track ASC";
    if(!$start) $start = 0;
		$res = $db->limitQuery($sql, $start, $hitsPerPage);
		if(DB::isError($res))
			raiseError($res);
    while (DB_OK === $res->fetchInto($item)) {
			$list[] = new sotf_Programme($item['id'], $item);
		}
		return $list;
	}

}	

?>