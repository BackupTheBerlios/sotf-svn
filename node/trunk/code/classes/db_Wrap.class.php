<?php

include_once($peardir . '/DB/pgsql.php');

class db_Wrap extends DB_pgsql {

	function getDBConn($dsn, $persistent) {
	  @$obj = & new db_Wrap;
	  debug("DB","connecting to: $dsn");
	  $dsninfo = DB::parseDSN($dsn);
	  $obj->connect($dsninfo, $persistent);
	  return $obj;
	}

	function errorNative() {
	  $err = parent::errorNative();
	  error_log("PGSQL error: $err",0);
	  error_log("in query: " . $this->last_query,0);
	  global $sqlDebug;
	  if($sqlDebug) {
		echo "<p><b>SQL error: $err</b> in <br>";
		echo $this->last_query . "</p>";
	  }
	  return $err;
	}

	function getSQLDate($timestamp='')
	  {
	    if(!$timestamp)
	      $timestamp = time();
	    $date = getdate($timestamp);
	    return $date['year'] . '-' . $date['mon'] . '-' . $date['mday'] . ' ' . $date['hours']. ':' . $date['minutes'];
	  }

	function formatTZ($sec) {
	  $h = intval($sec/3600);
	  $m = sprintf('%02d',abs(intval(($sec-$h*3600)/60)));
	  if($h{0} != '-')
	    $h = "+$h";
	  return "$h:$m";
	}

	function getTimestampTz($time = '') {
	  static $tz;
	  if(!$tz) {
	    $tz = db_Wrap::formatTZ(date('Z'));
	  }
	  if($time)
	    return date('Y-m-d H:i:s', $time) . " $tz";
	  else
	    return date('Y-m-d H:i:s') . " $tz";
	}

	function getTimestamp($time = '') {
	  if($time)
	    return date('Y-m-d H:i:s', $time);
	  else
	    return date('Y-m-d H:i:s');
	}

	function epoch() {
	  return "epoch";
	}

	function formatDateTime($fieldName, $formatString) {
		// Postgres
		return " to_char($fieldName, '$formatString') ";
		// MySQL:
		//return " date_format($fieldName, \"$formatString\") ";
	}

	function formatDate($fieldName) {
		// Postgres
		return " to_char($fieldName, 'DD.MM.YYYY') ";
		// MySQL:
		//return " date_format($fieldName, '%d-%b-%Y') ";
	}

	function formatTime($fieldName) {
		// Postgres
		return " to_char($fieldName, 'HH:MI') ";
		// MySQL:
		//return " date_format($fieldName, '%h:%i') ";
	}

	function formatDay($fieldName) {
		// Postgres
		return " to_char($fieldName, 'DD') ";
		// MySQL:
		//return " date_format($fieldName, '%e') ";
	}

	// just don't forget this...
	function limitQuery($query, $from, $count) {
	  global $sqlDebug;
	  if($sqlDebug)
	    debug("DB","LimitQuery: $from, $count, $query");
	  return parent::limitQuery($query, $from, $count);
	}
	
	function query($query) {
	  global $sqlDebug;
	  if($sqlDebug)
	    debug("DB","Query: $query");
	  return parent::query($query);
	}

	function escape_bytea($binary)
	{
		$bytea = "";
		for ($i=0;$i<strlen($binary);$i++)
			$bytea .= '\\\\'.sprintf("%03o",ord(substr($binary,$i,1)));
		return $bytea;
	}

	function unescape_bytea($bytea)
	{
		return eval("return \"".str_replace('$', '\\$', str_replace('"', '\\"', $bytea))."\";");
	}

	/** SELECT $field FROM $table WHERE $idKey = '$id' */
	function getBlob($table, $id, $idKey, $field)
	{
	    debug("DB","getBlob: Table: $table, ID: $id, IDKey: $idKey, Field: $field");
		return $this->unescape_bytea(parent::getOne("SELECT $field FROM $table WHERE $idKey = '$id'"));
	}

	/** UPDATE $table SET $field = '$binary' WHERE $idKey = '$id' */
	function setBlob($table, $id, $idKey, $field, $binary)
	{
	    debug("DB","setBlob: Table: $table, ID: $id, IDKey: $idKey, Field: $field");
		parent::query("UPDATE $table SET $field = '".$this->escape_bytea($binary)."' WHERE $idKey = '$id'");
	}

}

?>