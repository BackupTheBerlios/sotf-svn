<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: Andr�s Micsik, M�t� Pataki, Tam�s D�ri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

$okURL = sotf_Utils::getParameter('okURL');
if(!$okURL) {
     $okURL = $config['localPrefix'].'/';
}

sotf_User::logout();

$page->redirect("login.php?okURL=". urlencode($okURL));

?>
