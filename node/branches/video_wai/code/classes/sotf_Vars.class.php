<?php

/*  -*- tab-width: 3; indent-tabs-mode: 1; -*-
 * $Id: sotf_Vars.class.php 79 2003-01-31 10:33:31Z andras $
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

class sotf_Vars {

  /** Database connection */
  var $db;

  /** name of table to store variables */
  var $table;

  /** True if variables has been read from database */
  var $isInitialized = false;

  /** Persistent variables: array (name => value). */
  var $vars;

  /** Constructor */
  function sotf_Vars($db, $table_name) {
    $this->db = $db;
    $this->table = $table_name;
    $this->initialize();
  }

  /** Initialize object: read in all variables from database. */
  function initialize() {
    $res = $this->db->getAll("SELECT name, value FROM $this->table");
    if(DB::isError($res))
      raiseError($res);
    while(list(,$value) = each($res)) {
      $this->vars[$value['name']] = $value['value'];
    }
  }

  /** Returns the value of all persistent variables in an array (name => value). */
  function getAll() {
    return $this->vars;
  }

  /** Returns the value of a persistent variable. */
  function get($variable_name, $defaultValue) {
    if(isset($this->vars[$variable_name]))
      return $this->vars[$variable_name];
    else
      return $defaultValue;
  }

  /** Sets the value of a persistent variable. */
  function set($name,$val) {
    $name = sotf_Utils::magicQuotes($name);
    $val = sotf_Utils::magicQuotes($val);
    if(isset($this->vars[$name]))
      $update = 1;
    $this->vars[$name] = $val;
    if($update)
      $result = $this->db->query("UPDATE $this->table SET value='$val' WHERE name='$name'");
    else
      $result = $this->db->query("INSERT INTO $this->table (name,value) VALUES('$name', '$val')");
    if(DB::isError($result)) 
      raiseError($result);
    debug("setvar", "$name=$val");
  }

}
