<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-

/*
 * $Id: get.php 541 2006-02-15 15:27:56Z wreutz $
 */

require("init.inc.php");

// online counter for statistics
if ($config['counterMode']) {
   $chCounter_status = 'active';
   $chCounter_visible = 0;
   $chCounter_page_title = 'Premium Bezahlseite - protected.php';
   include($config['counterURL']);
}

$page->send();

?>