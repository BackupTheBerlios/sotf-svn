<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("config.inc.php");
require($classdir . '/sotf_Utils.class.php');
?>

<html>
<head><title><?php echo $nodeId?> log</title></head>
<body onChange="window.focus()">

<?php
/* This is for remote view of log file */

$full = sotf_Utils::getParameter("full");
$lines = sotf_Utils::getParameter("lines");
if(!$lines || !is_numeric($lines))
     $lines = 700;
if($full) {
  echo "<pre>";
  readfile($logFile);
  echo "</pre>";
} else {
  echo "<pre>";
  //echo implode("\n", sotf_Utils::tail($logFile, $lines));
  sotf_Utils::tail($logFile, $lines * 80);
  //system("tail -$lines $logFile");
  echo "</pre>";
}

echo "<a name=\"end\"></a>";

?>
