<?php  //-*- tab-width: 3; indent-tabs-mode: 1; -*-

class sotf_Playlist {

  var $items = array();

  function load() {
    global $user, $db;
    $query="SELECT prog_id as id, order_id, sotf_programmes.* FROM sotf_playlists".
      " LEFT JOIN sotf_programmes ON sotf_programmes.id = sotf_playlists.prog_id".
      " WHERE user_id = '$user->id' ORDER BY order_id";
    $this->items = $db->getAll($query);
    return $this->items;
  }

  function delete($progId) {
    global $user, $db;
		$db->query("DELETE FROM sotf_playlists WHERE user_id = '$user->id' AND prog_id='$progId' ");
  }
  
  function getFilename($progId) {
    global $db;
	return $db->getOne("SELECT filename FROM sotf_media_files WHERE prog_id='$progId' AND stream_access='t'");
  }

  function add($progId) {
    global $user, $db;
    $exist = $db->getOne("SELECT count(*) FROM sotf_playlists WHERE user_id='$user->id' AND prog_id='$progId' ");
    if (!$exist) {
      $maxId = $db->getOne("SELECT max(order_id) FROM sotf_playlists WHERE user_id='$user->id'");
      $maxId = $maxId + 1;
      $db->query("INSERT INTO sotf_playlists (prog_id, user_id, order_id) VALUES ('$progId', '$user->id', '$maxId')");
    }
  }

  function setOrder($progId, $orderId) {
    global $user, $db;
    $db->query("UPDATE sotf_playlists SET order_id = '$orderId' WHERE user_id = '$user->id' AND prog_id = '$progId'");
  }

  /** may be called as static method */
  function contains($progId) {
    global $user, $db;
    return $db->getOne("SELECT count(*) FROM sotf_playlists WHERE user_id = '$user->id' AND prog_id='$progId'");
  }

}
?>
