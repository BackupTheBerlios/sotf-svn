<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-

/*  -*- tab-width: 3; indent-tabs-mode: 1; -*-
 * $Id$
 *
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: Wolfgang Reutz
 */

class db_Wrap_mysql extends DB_mysql {

  /** When true, all executed SQL statements are logged. */
  var $debug = true;

  /** Name for DB connection. Used for logging. */
  var $name = '';

  /** When false, all SQL errors immediately stop execution and create an error page. */
  var $silent = false;

  /** When debug is on, logged query texts will be truncated to this length. */
  var $traceLength = 700;

  function makeConnection($dsn, $persistent, $name='') {
    if($this->debug)
      logger("mysql DB:$name","connecting to: $dsn");
    $this->name = $name;
    $dsninfo = DB::parseDSN($dsn);
    $success = $this->connect($dsninfo, $persistent);
    return $success;
  }
  
  function errorNative() {
    $err = parent::errorNative();
    //error_log("PGSQL error: $err",0);
    //error_log("in query: " . substr($this->last_query,0,254) ,0);
    if(!$this->silent) {
      if($this->debug)
        raiseError("SQL error: $err in \n " . $this->last_query);
      else
        raiseError("SQL error!");
    }
    return $err;
  }

  function limitQuery($query, $from, $count) {
    if($this->debug)
      logger("DB:".$this->name,"LimitQuery: $from, $count, " . substr($query,0, $this->traceLength));
    return parent::limitQuery($query, $from, $count);
  }
  
  function query($query) {
    if($this->debug) {
      logger("DB:".$this->name,"Query: " . substr($query, 0, $this->traceLength));
    }
    return parent::query($query);
  }
  
  function getSQLDate($timestamp='') {
    if(!$timestamp)
      $timestamp = time();
    $date = getdate($timestamp);
    return $date['year'] . '-' . $date['mon'] . '-' . $date['mday'] . ' ' . $date['hours']. ':' . $date['minutes'];
  }

  
  function formatDateTime($fieldName, $formatString) {
    return " date_format($fieldName, \"$formatString\") ";
  }
  
  function formatDate($fieldName) {
    return " date_format($fieldName, '%d-%b-%Y') ";
  }
  
  function formatTime($fieldName) {
    return " date_format($fieldName, '%h:%i') ";
  }
  
  function formatDay($fieldName) {
    return " date_format($fieldName, '%e') ";
  }
  
  /* the functions below have not been adaptted for Mysql yet... */

  /*
  function myTZ() {
    return $this->formatTZ(date('Z'));
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
  
  function diffTimestamp($t1, $t2) {
    $tt1 = strtotime($t1);
    $tt2 = strtotime($t2);
    $diff = $tt1 - $tt2;
    return $diff;
  }
  
  function epoch() {
    return "epoch";
  }

  function begin($serializable = false) {
    $succ = $this->query("BEGIN TRANSACTION");
    if($serializable)
      $succ = $this->query("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
    return $succ;
  }
  
  function commit() {
    return $this->query("COMMIT");
  }
  
  function rollback() {
    return $this->query("ROLLBACK");
  }
    
  // utility method for storing and loading binary data in postgres
  function escape_bytea($binary) {
    $bytea = "";
    for ($i=0;$i<strlen($binary);$i++)
      $bytea .= '\\'.sprintf("%03o",ord(substr($binary,$i,1)));
    return $bytea;
  }
  
  // utility method for storing and loading binary data in postgres
  function unescape_bytea($bytea) {
    return eval("return \"".str_replace('$', '\\$', str_replace('"', '\\"', $bytea))."\";");
  }
  
  // Loads a binary object from database. SELECT $field FROM $table WHERE $idKey = '$id'
  function getBlob($table, $id, $idKey, $field) {
    return $this->unescape_bytea(parent::getOne("SELECT $field FROM $table WHERE $idKey = '$id'"));
  }
  
  // Stores a binary object in database. UPDATE $table SET $field = '$binary' WHERE $idKey = '$id'
  function setBlob($table, $id, $idKey, $field, $binary) {
    parent::query("UPDATE $table SET $field = '".$this->escape_bytea($binary)."' WHERE $idKey = '$id'");
  }
  
*/

}

?>