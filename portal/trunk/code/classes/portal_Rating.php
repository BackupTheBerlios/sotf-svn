<?php

class Rating {

  function setRating($prog_id, $rating) {
	global $page, $user, $db;

	if($user->loggedIn()) {
	  $userid = $user->getId();
	  $query = "SELECT * FROM portal_ratings WHERE prog_id='$prog_id' AND user_id='$userid'";
	  $result = $db->getRow($query);
	  if($result != NULL) {
		$query = "UPDATE portal_ratings SET rate='$rating', host='" . getHostName() . "', ";
		$query .= "entered='" . $db->getTimestampTz() . "' ";
		$query .= "WHERE prog_id='$prog_id' AND user_id='$userid'";
	  } else {
		$query = "INSERT INTO portal_ratings (prog_id, user_id, rate, host, entered) 
                  VALUES('$prog_id', '$userid','$rating','" . getHostName() . "','" . $db->getTimestampTz() . "')";
	  }
	  $db->query($query);
	} else {
	  // anonymous rating
	  $key = $page->getAuthKey();
	  //debug("new rating", $key);
	  if($key) {
		$query = "SELECT rate FROM portal_ratings WHERE prog_id='$prog_id' AND auth_key='$key'";
		$result = $db->getOne($query);
		if($result != NULL) {
		  // update
		  if($result == $rating) {
			// we can spare an SQL query
		  } else {
			$query = "UPDATE portal_ratings SET rate='$rating', host='" . getHostName() . "', ";
			$query .= "entered='" . $db->getTimestampTz() . "' ";
			$query .= " WHERE prog_id='$prog_id' AND auth_key='$key'";
			$db->query($query);
		  }
		  $updateDone = true;
		}
	  } else {
		// TODO: if there was a rating request from the same host within x minutes, then reject
	  }
	  if(!$updateDone) {
		// insert
		if(!$key) {
		  $problem = "'no auth_key'";
		  $keySafe = "NULL";
		} else {
		  $problem = "NULL";
		  $keySafe = "'".$key."'";
		}
		$query = "INSERT INTO portal_ratings (prog_id, rate, host, entered, auth_key, problem) 
          VALUES('$prog_id','$rating','" . getHostName() . "','" . $db->getTimestampTz() . "', $keySafe, $problem)";
		$db->query($query);
	  }
	}
	// update instant rating
	$query = "select SUM(rate) as sum, count(rate) as count FROM portal_ratings WHERE prog_id='$prog_id' AND user_id IS NULL";
	$result = $db->getRow($query);
	$count_anon = $result['count'];
	$sum_anon = $result['sum'];

	$query = "select SUM(rate) as sum, count(rate) as count FROM portal_ratings WHERE prog_id='$prog_id' AND user_id IS NOT NULL";
	$result = $db->getRow($query);

	$count_reg = $result['count'];
	$sum_reg = $result['sum'];
	$ratingValue = ($sum_reg + $sum_anon) / ($count_reg + $count_anon);
	$query = "UPDATE portal_prog_rating SET rating_value=$ratingValue, " .
	   "rating_count_anon=$count_anon, rating_count_reg=$count_reg WHERE prog_id='$prog_id'";
	$result = $db->query($query);
	if ($db->affectedRows() == 0)
	{
		$query = "INSERT INTO portal_prog_rating(rating_value, rating_count_anon, rating_count_reg, prog_id) ".
			" VALUES($ratingValue, $count_anon, $count_reg, '$prog_id')";
		$result = $db->query($query);
	}
	
  }

  function getRating($prog_id) {
	global $page, $db;

	$prog_id = $prog_id;
	$query = "SELECT rating_value, rating_count_anon, rating_count_reg FROM portal_prog_rating ".
			   " WHERE prog_id='$prog_id'";
	$result = $db->getRow($query);

	return $this->getRatingFromCommentRecord($result);
  }

  function getRatingFromCommentRecord($array) {

	$count = $array['rating_count_reg'] + $array['rating_count_anon'];
	//debug("COUNT", $count);
	$v = $array['rating_value'];
	if($v)
	  $rtext = sprintf('%.2f', $array['rating_value']) . " (" . $count . ") ";
	else
	  $rtext = "-";
	return array(
				 RATING_OUTPUT => $rtext,
				 RATING_VALUE => $array['rating_value'],
				 RATING_COUNT => $array['rating_count_reg'] + $array['rating_count_anon'],
				 RATING_COUNT_REG => $array['rating_count_reg'],
				 RATING_COUNT_ANON => $array['rating_count_anon']
				 );
  }
  
  function getRatings() {
  global $page;
  return array(
  	0 => $page->getlocalized("rating_0"),
  	1 => $page->getlocalized("rating_1"),
  	2 => $page->getlocalized("rating_2"),
  	3 => $page->getlocalized("rating_3"),
  	4 => $page->getlocalized("rating_4"),
  	5 => $page->getlocalized("rating_5")
  );
  }

}

?>