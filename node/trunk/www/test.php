<?php
require("init.inc.php");

$res = $userdb->getOne("SELECT auth_id FROM authenticate WHERE username = 'akazcs'");
echo "'$res'";

#$page->send();

?>
