<?php
/** This special PHP doesn't use Smarty, because it generates HTML on-the-fly
    Works in Mozilla 1.0 and Internet Explorer 5.0 */

require("init.inc.php");

function exitPage()
{
	echo "<html>\n";
	echo "<body onLoad='self.close()'>\n";
	echo "</body>\n";
	echo "</html>\n";
	exit;
}

function progressMp3ToOgg($source,$target,$index)
{
	global $audioFormats;
}

function progressMp3ToMp3($source,$target,$index)
{
}

$id = sotf_Utils::getParameter('id'); 
$index = sotf_Utils::getParameter('index'); 

$prg = & new sotf_Programme($id);

$audioFiles = & new sotf_FileList();
$audioFiles->getAudioFromDir($prg->getAudioDir());
$checker = & new sotf_AudioCheck($audioFiles);

if ($checker->reqs[$index][0] === true)
	exitPage();

if ($checker->reqs[$index][1] !== false)
{
	$source = $audioFiles->list[$checker->reqs[$index][1]]->getPath();
	$target = $tmpdir . '/' . $prg->get('track') . '_' . $checker->getFormatFilename($index);
}
$bitrate = $audioFormats[$index]["bitrate"];
$samplerate = $audioFormats[$index]["samplerate"];
if ($audioFormats[$index]["channels"] == 1)
	$mode = "mono";
else
	$mode = "joint";
	
//echo "<pre>";
//echo "$lame --mp3input -b $bitrate -m $mode --resample $samplerate '$source' '$target' 2>&1";
//echo "</pre>";
//exit; // DEBUG
set_time_limit(600);
echo "<html>\n";
echo "<head>\n";
echo "<title>".$page->getlocalized("progress_bar")."</title>\n";
echo "<script>\n";
echo "window.height = 800;\n";
echo "</script>\n";
echo "</head>\n";
echo "<body>\n";

// 256 dummy bytes for IE
for ($i=0;$i<256;$i++)
	echo " ";

echo "<p align='center'>Converting in progress<br>\n";
echo "<p align='center'><nobr>";
for ($i=0;$i<100;$i++)
	echo "<img src='$localPrefix/static/back.png' border='0' width='3' height='30' id='img".$i."' />";
echo "</nobr></p>";
$fp = popen("$lame --mp3input -b $bitrate -m $mode --resample $samplerate $source $target 2>&1", 'r');
$line = "";
$out = 0;
$left = 100;
while(!feof($fp))
{
	$data = fread($fp,1);
	if ((ord($data) == 13) || (ord($data) == 10))
	{
		//echo "$line\n"; // DEBUG
		if ($asd = preg_match("/^.{6}?\/.{6}?.?\((..)%\)\|.*$/",$line,$match))
		{
			$curr = (integer) $match[1];
			echo "<script>\n";
			for ($i=$out;$i<$curr;$i++)
			{
				echo "var img = document.getElementById('img".$out."');\n";
				echo "img.src = '$localPrefix/static/fore.png';\n";
				$out++;
				$left--;
			}
			echo "</script>\n";
		}
		$line = "";
  	}
	else
		$line .= $data;
	flush();
}
pclose($fp);
echo "<script>\n";
while($left)
{
	echo "var img = document.getElementById('img".$out."');\n";
	echo "img.src = '$localPrefix/static/fore.png';\n";
	$left--;
	$out++;
}
echo "</script>\n";
flush();
echo "<pre>$line</pre><br>\n";
echo "</p>\n";
$prg->setAudio($target);
echo "<script>\n";
echo "alert('Convert ready!');\n";
echo "document.location.href='closeAndRefresh.php';\n";
//echo "self.close();";
echo "</script>\n";
echo "</body>\n";
echo "</html>\n";
?>