<?php

class sotf_Rating  extends sotf_Object {

  var $minValue = 1;
  var $maxValue = 5;

	function sotf_Rating($id='', $data='') {
    debug("new rating object", $id);
		$this->sotf_Object('sotf_ratings', $id, $data);
	}

  function find($objId, $userId, $nodeId=0) {
    $res = $this->db->getRow("SELECT * FROM sotf_ratings WHERE prog_id='$objId' AND user_id='$userId' AND user_node_id='$nodeId'");
    if($res && !DB::isError($res))
      $this->setAll($res);
  }

  function findAnon($objId, $authKey) {
    $res = $this->db->getRow("SELECT * FROM sotf_ratings WHERE prog_id='$objId' AND auth_key='$authKey'");
    //debug("findAnon", $res);
    if($res && !DB::isError($res))
      $this->setAll($res);
  }

  function findInstant($objId) {
    $res = $this->db->getRow("SELECT * FROM sotf_prog_rating WHERE prog_id='$objId'");
    if($res && !DB::isError($res))
      return new sotf_NodeObject('sotf_prog_rating', $res['id'], $res);
    else
      return new sotf_NodeObject('sotf_prog_rating');
  }

  function setRating($objId, $rating, $remoteData=array()) {
    global $db, $page, $user;
    
    // checks
    if(!is_numeric($rating) || $rating < $this->minValue || $rating > $this->maxValue) {
      addError("invalid or empty rating");
      return;
    }

    if(!empty($remoteData)) {
      // this rating comes from another node...
      // TODO
    } elseif($page->loggedIn()) {
      // local rating from registered user
      $this->find($objId, $user->id);
      $this->set('rate', $rating);
      $this->set('host', getHostName());
      $this->set('entered', $this->db->getTimestampTz());
      if($this->exists()) {
        // change existing rating
        $this->update();
      } else {
        // new rating
        $this->set('prog_id', $objId);
        $this->set('user_id', $user->id);
        $this->set('user_node_id', 0);
        $this->create();
      }        
    } else {
      // anonymous rating
      // TODO: if there was a rating request from the same host within x minutes, then reject
      $key = $page->getAuthKey();
      if($key) {
        $this->findAnon($objId, $key);
        $this->set('rate', $rating);
        $this->set('host', getHostName());
        $this->set('entered', $this->db->getTimestampTz());
        if($this->exists()) {
          // change existing rating
          $this->update();
        } else {
          // new rating
          $this->set('prog_id', $objId);
          $this->set('auth_key', $key);
          $this->create();
        }
      } else {
        addError($page->getlocalized("cannot_rate_no_authkey"));
        // or $this->set('problem', 'no_auth_key');
        return;
      }
    }
    $this->updateInstant($objId);
  }

  /** calculate overall rating value for object */
  function updateInstant($objId) {
    // update instant rating
    $instant = $this->findInstant($objId);
    $anon = $this->db->getRow("select SUM(rate) as sum, count(rate) as count FROM sotf_ratings " .
               "WHERE prog_id='$objId' AND user_id IS NULL");
    $instant->set('rating_count_anon', $anon['count']);
    $instant->set('rating_sum_anon', $anon['sum']);
    $reg = $this->db->getRow("select SUM(rate) as sum, count(rate) as count FROM sotf_ratings " .
               "WHERE prog_id='$objId' AND user_id IS NOT NULL");
    $instant->set('rating_count_reg', $reg['count']);
    $instant->set('rating_sum_reg', $reg['sum']);
    $instant->set('rating_count', $reg['count'] + $anon['count']);
    $ratingValue = ($reg['sum'] + $anon['sum']) / ($reg['count'] + $anon['count']);
    $ratingValue = sprintf('%.2f', $ratingValue);
    $instant->set('rating_value', $ratingValue);
    if($instant->exists()) {
      $instant->update();
    } else {
      $instant->set('prog_id', $objId);
      $instant->create();
    }
  }

  function getInstantRating($objId) {
    $instant = $this->findInstant($objId);
    if(!$instant->exists())
      return null;
    else {
      $retval = $instant->getAll();
      return $retval;
    }
  }

  function getMyRating($objId) {
    global $page, $user;
    if(!$page->loggedIn()) {
      $key = $page->getAuthKey();
      if($key) {
        $this->findAnon($objId, $key);
        if($this->exists())
          return $this->get('rate');
      }
    } else {
      $this->find($objId, $user->id);
      if($this->exists())
        return $this->get('rate');
      
    }
    return NULL;
  }

}

?>