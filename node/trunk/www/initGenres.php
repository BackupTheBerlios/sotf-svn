<?php
require("init.inc.php");

$db->query("DELETE FROM sotf_node_objects WHERE id LIKE '%ge%'");
$db->query("DELETE FROM sotf_genres");
$db->query("SELECT setval('sotf_genres_seq', 1, false)");

function createGenre($id, $english) {
  $o1 = new sotf_NodeObject("sotf_genres");
  $o1->set('genre_id', $id);
  $o1->set('language', 'en');
  $o1->set('name', $english);
  $o1->create();
}

createGenre( 1, 'Actuality');
createGenre( 2, 'Advert');
createGenre( 3, 'Announcement');
createGenre( 4, 'Call-in show');
createGenre( 5, 'Children / youth');
createGenre( 6, 'Comedy');
createGenre( 7, 'Dance');
createGenre( 8, 'Documentary');
createGenre( 9, 'Drama');
createGenre( 10, 'Education');
createGenre( 11, 'Feature / spot');
createGenre( 12, 'Game show');
createGenre( 13, 'Interview');
createGenre( 14, 'Jingle');
createGenre( 15, 'Magazine');
createGenre( 16, 'Mocroprogramme');
createGenre( 17, 'Music');
createGenre( 18, 'News');
createGenre( 19, 'Oral history / storytelling');
createGenre( 20, 'Talk show / discussion');
createGenre( 21, 'Training');

?>

<h2>initialized genres</h2>
