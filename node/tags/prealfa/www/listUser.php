<?php
require("init.inc.php");

$users = sotf_User::listUsers();
$smarty->assign('USERS',$users);
$smarty->display("listUsers.htm");
?>
