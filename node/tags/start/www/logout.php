<?php
require("init.inc.php");

$okURL = sotf_Utils::getParameter('okURL');
if(!$okURL) {
     $okURL = $localPrefix.'/';
}

sotf_User::logout();

$page->redirect("login.php?okURL=". urlencode($okURL));

?>
