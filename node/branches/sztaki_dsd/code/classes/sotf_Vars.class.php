<?php

class sotf_Vars {

  var $db;

  var $table;

  var $vars;

  function sotf_Vars($db, $table_name) {
    $this->db = $db;
    $this->table = $table_name;
  }

  function get($variable_name) {
    if (isset($this->vars[$variable_name]))
    return $this->vars[$variable_name];
  
    $query = "SELECT value FROM sotf_vars WHERE name='$variable_name'";
    $result = $this->db->getOne($query);
    if(DB::isError($result)) {
      raiseError($result->getMessage());
    }
    debug("getvar", "$variable_name=$result");
    $this->vars[$variable_name] = $result;
    return $result;
  }

  function set($name,$val) {
    $this->vars[$name] = $val;
    sotf_Base::setData("sotf_vars", array('name' => $name, 'value' => $val), 'name');
    debug("setvar", "$name=$val");
  }

}
