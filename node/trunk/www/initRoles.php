<?php
require("init.inc.php");

$db->query("DELETE FROM sotf_node_objects WHERE id LIKE '%rn%'");
$db->query("DELETE FROM sotf_node_objects WHERE id LIKE '%ro%'");
$db->query("DELETE FROM sotf_roles");
$db->query("DELETE FROM sotf_role_names");
$db->query("SELECT setval('sotf_roles_seq', 1, false)");
$db->query("SELECT setval('sotf_role_names_seq', 1, false)");

function createRole($id, $english, $creator = 'f') {
  $o1 = new sotf_NodeObject("sotf_roles");
  $o1->set('role_id', $id);
  $o1->set('creator', $creator);
  $o1->create();
  $o2 = new sotf_NodeObject("sotf_role_names");
  $o2->set('role_id', $id);
  $o2->set('language', 'en');
  $o2->set('name', $english);
  $o2->create();
}

createRole( 1, 'Artist');
createRole( 2, 'Author');
createRole( 3, 'Commentator');
createRole( 4, 'Composer');
createRole( 5, 'Copyright holder');
createRole( 6, 'Correspondent');
createRole( 7, 'Designer');
createRole( 8, 'Director');
createRole( 9, 'Editor');
createRole( 10, 'Funder / Sponsor');
createRole( 11, 'Interviewee');
createRole( 12, 'Interviewer');
createRole( 13, 'Narrator');
createRole( 14, 'Participant');
createRole( 15, 'Performer');
createRole( 16, 'Producer');
createRole( 17, 'Production Personnel');
createRole( 18, 'Speaker');
createRole( 19, 'Transcriber');
createRole( 20, 'Translator');
createRole( 21, 'Other');

?>

<h2>initialized roles</h2>
