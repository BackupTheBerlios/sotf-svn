<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-

/* 
 * $Id$
 *
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *				at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

class sotf_Rating	 extends sotf_Object {

	var $minValue = 1;
	var $maxValue = 5;

	function sotf_Rating($id='', $data='') {
		$this->sotf_Object('sotf_ratings', $id, $data);
	}

	function find($objId, $userId, $nodeId=0) {
		global $db;

		$res = $db->getRow("SELECT * FROM sotf_ratings WHERE prog_id='$objId' AND user_id='$userId' AND user_node_id='$nodeId'");
		if($res && !DB::isError($res))
			$this->setAll($res);
	}

	function findAnon($objId, $authKey) {
		global $db;

		$res = $db->getRow("SELECT * FROM sotf_ratings WHERE prog_id='$objId' AND auth_key='$authKey'");
		//debug("findAnon", $res);
		if($res && !DB::isError($res))
			$this->setAll($res);
	}

	function findInstant($objId) {
	global $db;

		$res = $db->getRow("SELECT * FROM sotf_prog_rating WHERE prog_id='$objId'");
		if($res && !DB::isError($res))
			return new sotf_NodeObject('sotf_prog_rating', $res['id'], $res);
		else
			return new sotf_NodeObject('sotf_prog_rating');
	}

	function recordRating($data) {		

	  // checks
	  $rating = $data['rate'];
	  if(!is_numeric($rating) || $rating < $this->minValue || $rating > $this->maxValue) {
		 raiseError("invalid or empty rating");
		 return;
	  }
	  
	  if($data['user_id']) {
		 // local rating from registered user
		 $this->find($data['prog_id'], $data['user_id'], $data['user_node_id']);
		 if($this->exists()) {
			// change existing rating
			$id = $this->id;
			$this->setAll($data);
			$this->setId($id);
			$this->update();
		 } else {
			// new rating
			$this->setAll($data);
			$this->create();
		 }				 
	  } else {
		 // anonymous rating
		 // TODO: if there was a rating request from the same host within x minutes, then reject
		 $key = $data['auth_key'];
		 if($key) {
			$this->findAnon($data['prog_id'], $key);
			if($this->exists()) {
			  // change existing rating
			  $id = $this->id;
			  $this->setAll($data);
			  $this->setId($id);
			  $this->update();
			} else {
			  // new rating
			  $this->setAll($data);
			  $this->create();
			}
		 } else {
			raiseError($page->getlocalized("cannot_rate_no_authkey"));
			// or $this->set('problem', 'no_auth_key');
			return;
		 }
	  }
	}

	function sendRemoteRating($obj, $value) {
	  $data = $this->createLocalRatingInfo($obj->id, $value);
	  $this->recordRating($data);
	  $obj->createForwardObject('rating', $data);
	}

	function createLocalRatingInfo($objId, $value) {
	  global $db, $user, $config, $page;

	  $data = array('prog_id' => $objId,
						 'user_node_id' => $config['nodeId'],
						 'user_id' => $user->id,
						 'rate' => $value,
						 'host' => getHostName(),
						 'entered' => $db->getTimestampTz(),
						 'auth_key' => $page->getAuthKey());
	  return $data;
	}

	function setRating($objId, $rating) {
	  $data = $this->createLocalRatingInfo($objId, $rating);
	  $this->recordRating($data);
	  sotf_Object::addToUpdate('ratingUpdate', $data['prog_id']);
	  //$this->updateInstant($objId);
	}

	function setRemoteRating($data) {
		$this->recordRating($data);
		sotf_Object::addToUpdate('ratingUpdate', $data['prog_id']);
		//$this->updateInstant($data['prog_id']);
	}

	/** calculate overall rating value for object */
	function updateInstant($objId) {
		global $db;

		// update instant rating
		$instant = $this->findInstant($objId);
		$anon = $db->getRow("select SUM(rate) as sum, count(rate) as count FROM sotf_ratings " .
							 "WHERE prog_id='$objId' AND user_id IS NULL");
		$instant->set('rating_count_anon', $anon['count']);
		$instant->set('rating_sum_anon', $anon['sum']);
		$reg = $db->getRow("select SUM(rate) as sum, count(rate) as count FROM sotf_ratings " .
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