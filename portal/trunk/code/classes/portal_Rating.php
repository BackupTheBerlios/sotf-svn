<?php

class Rating {

  function setRating($comment_id, $rating) {
	global $page;

	$db = &$page->db;;
	if($page->loggedInNotAnonym()) {
	  $userid = clean($page->getUserId());
	  $db->query("SELECT * FROM rating_atomic WHERE comment_id='$comment_id' AND userid='$userid'");
	  if($db->next_record()) {
		$query = "UPDATE rating_atomic SET rate='$rating', host='" . getHostName() . "', ";
		$query .= "entered='" . getSQLDate() . "' ";
		$query .= "WHERE comment_id='$comment_id' AND userid='$userid'";
	  } else {
		$query = "INSERT INTO rating_atomic (comment_id, userid, rate, host, entered) 
                  VALUES('$comment_id', '$userid','$rating','" . getHostName() . "','" . getSQLDate() . "')";
	  }
	  $db->query($query);
	} else {
	  // anonymous rating
	  $key = $page->getAuthKey();
	  //debug("new rating", $key);
	  if($key) {
		$db->query("SELECT rate FROM rating_atomic WHERE comment_id='$comment_id' AND auth_key='$key'");
		if($db->next_record()) {
		  // update
		  if($db->Record['rate'] == $rating) {
			// we can spare an SQL query
		  } else {
			$query = "UPDATE rating_atomic SET rate='$rating', host='" . getHostName() . "', ";
			$query .= "entered='" . getSQLDate() . "' ";
			$query .= " WHERE comment_id='$comment_id' AND auth_key='$key'";
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
		  $keySafe = "'".clean($key)."'";
		}
		$query = "INSERT INTO rating_atomic (comment_id, rate, host, entered, auth_key, problem) 
          VALUES('$comment_id','$rating','" . getHostName() . "','" . getSQLDate() . "', $keySafe, $problem)";
		$db->query($query);
	  }
	}
	// update instant rating
	$db->query("select SUM(rate) as sum, count(rate) as count FROM rating_atomic " .
			   "WHERE comment_id='$comment_id' AND userid IS NULL");
	$db->next_record();
	$count_anon = $db->Record['count'];
	$sum_anon = $db->Record['sum'];
	$db->query("select SUM(rate) as sum, count(rate) as count FROM rating_atomic " .
			   "WHERE comment_id='$comment_id' AND userid IS NOT NULL");
	$db->next_record();
	$count_reg = $db->Record['count'];
	$sum_reg = $db->Record['sum'];
	$ratingValue = ($sum_reg + $sum_anon) / ($count_reg + $count_anon);
	$query = "UPDATE psl_comment SET rating_value=$ratingValue, " .
	   "rating_count_anon=$count_anon, rating_count_reg=$count_reg WHERE comment_id='$comment_id'";
	$db->query($query);
  }

  function getRating($comment_id) {
	global $page;

	$comment_id = clean($comment_id);
	$db = &$page->db;;
	$db->query("SELECT rating_value, rating_count_anon, rating_count_reg FROM psl_comment ".
			   " WHERE comment_id='$comment_id'");
	$db->next_record();
	return getRatingFromCommentRecord($db->Record);
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
  

}

?>