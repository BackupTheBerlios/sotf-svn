<?php
require("init.inc.php");

$page->forceLogin();

checkPerm('node', "change");

header("Content-type: text/plain\n");

readfile("config.inc.php")

?>
