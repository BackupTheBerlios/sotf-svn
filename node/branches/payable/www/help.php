<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-

/*
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("config.inc.php");

$action = $_GET['action'];
$lang = $_GET['lang'];

header("Location: " . $config['localPrefix'] . "/help/index.$lang.html#$action");

// online counter for statistics
if ($config['counterMode']) {
   $chCounter_status = 'active';
   $chCounter_visible = 0;
   $chCounter_page_title = 'Hilfe - help.php';
   include($config['counterURL']);
}

?>
