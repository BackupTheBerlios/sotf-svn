<?php //-*- tab-width: 3; indent-tabs-mode: 1; -*-

class sotf_PrgList {

  var $plist = array();

  /** constructor: takes an array of sotf_Programme objects as argument */
  function sotf_PrgList($prgs) {
    $this->plist = $prgs;
  }

  function getList() {
    return $this->plist;
  }

}

?>