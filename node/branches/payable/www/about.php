<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-

/*
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

// online counter for statistics
if ($config['counterMode']) {
   $chCounter_status = 'active';
   $chCounter_visible = 0;
   $chCounter_page_title = 'Über uns - about.php';
   include($config['counterURL']);
}

$page->send();

?>
