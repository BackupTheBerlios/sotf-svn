<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu

Cleans up Postgres DB in case of cascading delete doesn't work.
Not complete yet!
 */

require("init.inc.php");
$page->forceLogin();
checkPerm('node', "change");

?>

<html>
<head><title><?php echo $config['nodeId']?> cleanup</title></head>
<body onChange="window.focus()">

<?php

 //$config['debug_type'] = 'now';

set_time_limit(18000);

$repository->cleanTables();

?>
<h3>Tables cleaned successfully.</h3>