<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-

/*
 * $Id: partner.php 581 2006-11-15 00:27:56Z clemens $
 */

require("init.inc.php");

// online counter for statistics
if ($config['counterMode']) {
   $chCounter_status = 'active';
   $chCounter_visible = 0;
   $chCounter_page_title = 'Partner - partner.php';
   include($config['counterURL']);
}

$page->send();

?>
