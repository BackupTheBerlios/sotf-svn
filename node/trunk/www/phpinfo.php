<?php
require("init.inc.php");

$page->forceLogin();
if (!hasPerm('node', "change")) {
  raiseError("You have no permission for this!");
}

phpinfo();

?>
