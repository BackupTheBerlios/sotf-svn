<?php

require("../portal_login.php");

$result = $portal -> getPortals();
$portals = array();
foreach ($result as $portal) $portals[$portal['id']] = $portal['name'];
$smarty->assign("portals", $portals);


$page->send("admin_index.htm");

?>