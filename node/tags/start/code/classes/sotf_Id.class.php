<?php

define("ID_DELIMITER", ':');

class sotf_Id {

  // TODO: forbidden characters: : / etc.

var $station;

var $date;

var $track;

 function sotf_Id($stationId, $date, $trackId) {
   $this->station = $stationId;
   $this->date = $date;
   $this->track = $trackId;
   if(!$date) 
	 $this->date = date('Y-m-d');
 }

 function parseId($idString) {
   $pos = strpos($idString, ID_DELIMITER);
   $stationId = substr($idString, 0, $pos);
   $rest = substr($idString, $pos+1);
   $pos = strpos($rest, ID_DELIMITER);
   $date = substr($rest, 0, $pos);
   $rest = substr($rest, $pos+1);
   $trackId = $rest;
   return new Id($stationId, $date, $trackId);
   
 }

 function toString() {
   return $this->station . ID_DELIMITER . $this->date . ID_DELIMITER . $this->track;
 }

}

?>
