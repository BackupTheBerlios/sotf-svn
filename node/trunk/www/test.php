<?php
require("init.inc.php");

//$res = $userdb->getOne("SELECT auth_id FROM authenticate WHERE username = 'akazcs'");
//echo "'$res'";

#$page->send();

$repository->updateTopicCounts();

$mainContent = false;
echo ($mainContent ? 't' : 'f');
?>
<h2>updateTopicCounts() ready</h2>

