<?php
require("init.inc.php");

//$res = $userdb->getOne("SELECT auth_id FROM authenticate WHERE username = 'akazcs'");
//echo "'$res'";

#$page->send();

///$repository->updateTopicCounts();

/*
$mainContent = false;
echo ($mainContent ? 't' : 'f');

if(is_array(NULL))
     print "ARRAY IS NULL";

$k = '';
$k= 0;
if(empty($k))
     print("empty");
     else
     print("not");
*/


//$prog = new sotf_Programme('001pr4');
//$prog->setBlob('icon', $prog->get('icon'));

//echo "Icon saved";

$ids = $db->getCol("select id from sotf_programmes");
while(list(, $id) = each($ids)) {
  $obj = new sotf_Programme($id);
  $icon = $obj->getBlob('icon');
  $obj->saveBlob('icon', $icon);
  echo "<br>icon saved for $id";
}

$ids = $db->getCol("select id from sotf_stations");
while(list(, $id) = each($ids)) {
  $obj = new sotf_Station($id);
  $icon = $obj->getBlob('icon');
  $obj->saveBlob('icon', $icon);
  echo "<br>icon saved for $id";
}

$ids = $db->getCol("select id from sotf_series");
while(list(, $id) = each($ids)) {
  $obj = new sotf_Series($id);
  $icon = $obj->getBlob('icon');
  $obj->saveBlob('icon', $icon);
  echo "<br>icon saved for $id";
}

$ids = $db->getCol("select id from sotf_contacts");
while(list(, $id) = each($ids)) {
  $obj = new sotf_Contact($id);
  $icon = $obj->getBlob('icon');
  $obj->saveBlob('icon', $icon);
  echo "<br>icon saved for $id";
}

?>


