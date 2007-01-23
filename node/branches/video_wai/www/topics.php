<?php  // -*- tab-width: 3; indent-tabs-mode: 1; -*- 



/*  

 * $Id: topics.php 386 2005-06-24 14:33:06Z wreutz $

 * Created for the StreamOnTheFly project (IST-2001-32226)

 * Authors: András Micsik, Máté Pataki, Tamás Déri 

 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu

 */



require("init.inc.php");



$smarty->assign("openTree", sotf_Utils::getParameter("open"));

$smarty->display("topics.htm");



?>