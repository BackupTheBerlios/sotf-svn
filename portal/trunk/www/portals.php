<?php

require("portal_login.php");
$portals = $portal -> getPortals();
foreach ($portals as $p) print("<a href=\"portal.php/".$p['name']."\">".$p['name']."</a><br>");

?>


