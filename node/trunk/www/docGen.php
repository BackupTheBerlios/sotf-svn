<?php
require("config.inc.php");
require($classdir . '/sotf_Utils.class.php');

// where phpdocgen files are located
$phpdocgendir = 'C:/sotf/helpers/phpdocgen';

// where perl is located
$perl = 'C:/perl/bin/perl.exe';

// where to put documentation
$docdir = realpath("$basedir/code/doc/php");

$phpDirFiles = array();
$phpClassFiles = array();
if ($dir = opendir("."))
{
	while (($file = readdir($dir)) !== false)
		if (preg_match("/\.php$/",$file)) {
			$phpDirFiles[] = realpath($file);
    }
	closedir($dir);
}
if ($dir = opendir($classdir))
{
	while (($file = readdir($dir)) !== false)
		if (preg_match("/\.class\.php$/",$file)) {
      $phpClassFiles[] = $classdir . "/$file";
    }
	closedir($dir);
}

$script = realpath("$phpdocgendir/phpdocgen.pl");
if (is_dir($docdir))
	sotf_Utils::erase($docdir);
?>
<html>
<head>
<title>Documentation generation</title>
</head>
<body>
<p><b>Target dir</b>: <?php echo $docdir ?></p>
<p><b>phpdocgen output</b>:</p>
<pre>
<?php
passthru("$perl $script --output=$docdir --defpack=StreamOnTheFly " . /* implode(" ",$phpDirFiles) .*/ " "  . implode(" ",$phpClassFiles)." 2>&1");
?>
</pre>
</body>
</html>
