<?php
require("init.inc.php");

$st = new sotf_Station();
$st->hasPermission('create');
//$st->set('description', 'bla-bla');
//$st->save();

//trigger_error("ize", E_USER_ERROR);

//$smarty->assign_by_ref('STATION', $st);
$page->send();

?>
