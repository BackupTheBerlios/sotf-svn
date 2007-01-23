<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id: help.php 250 2003-06-25 14:57:56Z andras $
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("config.inc.php");

$action = $_GET['action'];
$lang = $_GET['lang'];

header("Location: " . $config['localPrefix'] . "/help/index.$lang.html#$action");

?>