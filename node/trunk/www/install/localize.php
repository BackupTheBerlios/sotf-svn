<?php

require("../config.inc.php");

require($config['smartydir'] . '/Smarty.class.php');
require($config['smartydir'] . '/Config_File.class.php');

header("Content-Type: text/plain");

$lang = $_GET['lang'];
if(!$lang)
     die("lang missing");

$config_dir = $config['basedir'] . "/code/configs";

$engFile = $config_dir . "/eng.conf";
$newFile = $config_dir . "/$lang.conf";

if(is_file($newFile)) {
  $langConf = new Config_File($config_dir);
  $langConf->load_file($newFile, false);
  rename($newFile, "$newFile.old") || die("could not make backup");
}

$eng = file($engFile);

$out = fopen($newFile, "wb");
if(!$out)
     die("could not write into: $newFile");

$section = NULL;
foreach($eng as $line) {
  if(preg_match('/"{3}/', $line)) {
    die('please do not use multiline (""")');
  } elseif(preg_match('/^([^=]+)=\s*(.*)/', $line, $m)) {
    $key = trim($m[1]);
    $val = trim($m[2]);
    //print("$section: $key = $val\n");
    if($langConf && $key) {
      $transl = $langConf->get("$lang.conf", $section, $key);
      //print_r($transl);
    } else
      $transl = NULL;
    if(!empty($transl) && !is_array($transl)) 
      $newLine = "$key = $transl\n";
    else {
      $newLine = "$key = $val (*)\n";
      print("Missing translation: [$section] $key\n");
    }
  } else {
    if(preg_match('/^\[(.*?)\]/', $line, $m)) {
      $section = $m[1];
    } else {
      if(preg_match('/^[\s\n]*$/', $line)) {
        // empty line
      } elseif(preg_match('/^\s*#/', $line)) {
        // comment
      } else {
        print("Strange line: $line\n");
      }
    }
    $newLine = $line;
  }
  fwrite($out, $newLine);
}

print("Kesz");


?>