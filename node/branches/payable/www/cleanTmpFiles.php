<?php
/*  -*- tab-width: 3; indent-tabs-mode: 1; -*-
 * $Id: cron.php 403 2005-09-01 08:12:38Z micsik $
 */

require("init.inc.php");

checkAdminAccess();
excludeRobots();

/** This page has to be called frequently (e.g. using wget) 
 to clean premium links.
*/

function line($msg) { // just for screen output (testing)
  echo "<br>$msg\n";
}

debug("cleaning", "tmpDir");
$clearTime = time() - 8*60*60;
$dir = dir($config['tmpDir']);
while($entry = $dir->read()) {
  if ($entry == "." || $entry == "..")
    continue;
  $file = $config['tmpDir'] . "/$entry";
  if(is_dir($file))
    continue;
  //if (preg_match('/\.png$/', $entry) || preg_match('/\.m3u$/', $entry)) {
  if(is_file($file) && filemtime($file) < $clearTime) {
    if(!unlink($file))
      logError("could not delete: $file");
  }
}
$dir->close();

line("CLEANED TMP DIR");

?>