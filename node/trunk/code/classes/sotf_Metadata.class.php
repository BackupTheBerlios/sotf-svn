<?php

/***
 * 
 * 
 * 
 ************/

class sotf_Metadata {		

  var $fields;

  function fillExtra($id) {
    $this->fields = $this->db->getAll("SELECT * FROM sotf_extradata WHERE id='" . $id . "'");
  }

  function saveExtra($id) {
  }

}

?>