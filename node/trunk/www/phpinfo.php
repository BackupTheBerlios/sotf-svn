<?php
require("init.inc.php");

$page->forceLogin();

checkPerm('node', "change");

phpinfo();

?>
