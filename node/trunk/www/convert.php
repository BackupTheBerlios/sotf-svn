<?php
/** This special PHP doesn't use Smarty, because it generates HTML on-the-fly */

require("init.inc.php");

$page->popup = true;

function startPage()
{
	set_time_limit(60000);
	echo "<html>\n";
	echo "<head>\n";
	echo "<title>Progress</title>\n";
	echo "</head>\n";
	echo "<body>\n";

	// 256 dummy bytes for IE
	for ($i=0;$i<256;$i++)
		echo " ";
}

function endPage()
{
	echo "<script>\n";
	echo "alert('Convert ready!');\n";
	echo "document.location.href='closeAndRefresh.php';\n";
	echo "</script>\n";
	echo "</body>\n";
	echo "</html>\n";
}

function exitPage()
{
	echo "<html>\n";
	echo "<body onLoad='self.close()'>\n";
	echo "</body>\n";
	echo "</html>\n";
	exit;
}

function getTempWavName()
{
	global $tmpdir;

	$tempname = tempnam($tmpdir,"__");
	unlink($tempname);
	return $tempname. ".wav";
}

function progressBar($cmd,$regexp)
{
	global $progressBarChar;
	global $progressBarLength;

	$line = "";
	$out = 0;
	$left = $progressBarLength;

	$fp = popen($cmd . ' 2>&1', 'r');
	while(!feof($fp))
	{
		$data = fread($fp,1);
		if ((ord($data) == 13) || (ord($data) == 10))
		{
			if (preg_match($regexp,$line,$match))
			{
				$curr = (integer) (((integer) $match[1]) * $progressBarLength / 100);
				for ($i=$out;$i<$curr;$i++)
				{
					echo $progressBarChar;
					$out++;
					$left--;
				}
			}
			$line = "";
	  	}
		else
			$line .= $data;
		flush();
	}
	pclose($fp);
	while($left)
	{
		echo $progressBarChar;
		$left--;
		$out++;
	}
	flush();
}

function encodeWithLame($cmd)
{
	global $progressBarLength;
	global $progressBarChar;
	global $lameencRegexp;

	echo "<p>Encoding MP3 file...<br />\n";
	flush();

	progressBar($cmd,$lameencRegexp);
	echo "</p>\n";
	flush();
}

function decodeWithLame($cmd)
{
	global $progressBarLength;
	global $progressBarChar;

	echo "<p>Decoding MP3 file to PCM data...<br />\n";
	flush();
	exec($cmd);
	for ($i=0;$i<$progressBarLength;$i++)
		echo $progressBarChar;
	echo "</p>\n";
	flush();
}

function encodeWithOgg($cmd)
{
	global $progressBarLength;
	global $progressBarChar;
	global $oggencRegexp;

	echo "<p>Encoding OGG file...<br />\n";
	flush();
	progressBar($cmd,$oggencRegexp);
	echo "</p>\n";
	flush();
}

function decodeWithOgg($cmd)
{
	global $progressBarLength;
	global $progressBarChar;

	echo "<p>Decoding OGG file to PCM data...<br />\n";
	flush();
	exec($cmd);
	for ($i=0;$i<$progressBarLength;$i++)
		echo $progressBarChar;
	echo "</p>\n";
	flush();
}

function convertWithSox($cmd)
{
	global $progressBarLength;
	global $progressBarChar;

	echo "<p>Convert mono PCM data to stereo...<br />\n";
	flush();
	exec($cmd);
	for ($i=0;$i<$progressBarLength;$i++)
		echo $progressBarChar;
	echo "</p>\n";
	flush();
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
	// We have a better quality audio
	$sourceindex = $checker->reqs[$index][1];
}
else
{
	// We don't have a better quality audio, get the best
	$sourceindex = $checker->getBest();
}

if ($sourceindex === false)
	exitPage();

$source = $audioFiles->list[$sourceindex]->getPath();
$target = $tmpdir . '/' . $prg->get('track') . '_' . $checker->getFormatFilename($index);

$bitrate = $audioFormats[$index]["bitrate"];
$samplerate = $audioFormats[$index]["samplerate"];
if ($audioFormats[$index]["channels"] == 1)
	$mode = "mono";
else
	$mode = "joint";
	

startPage();
if (($audioFormats[$index]['format'] == 'mp3') && ($audioFiles->list[$sourceindex]->format == 'mp3'))
{
	if (($audioFormats[$index]['channels'] == 2) && ($audioFiles->list[$sourceindex]->channels == 1))
	{
		$tempname1 = getTempWavName();
		decodeWithLame("$lame --decode \"$source\" \"$tempname1\"");
		$tempname2 = getTempWavName();
		convertWithSox("$sox \"$tempname1\" -c2 \"$tempname2\"");
		unlink($tempname1);
		encodeWithLame("$lame --disptime 1 --cbr -b $bitrate -m $mode --resample $samplerate \"$tempname2\" \"$target\"");
		unlink($tempname2);
	}
	else
	{
		encodeWithLame("$lame --disptime 1 --cbr --mp3input -b $bitrate -m $mode --resample $samplerate \"$source\" \"$target\"");
	}
}
elseif (($audioFormats[$index]['format'] == 'ogg') && ($audioFiles->list[$sourceindex]->format == 'mp3'))
{
	$tempname1 = getTempWavName();
	decodeWithLame("$lame --decode \"$source\" \"$tempname1\"");
	if (($audioFormats[$index]['channels'] == 2) && ($audioFiles->list[$sourceindex]->channels == 1))
	{
		$tempname2 = getTempWavName();
		convertWithSox("$sox \"$tempname1\" -c2 \"$tempname2\"");
		unlink($tempname1);
		encodeWithOgg("$oggenc -b $bitrate -m $bitrate -M $bitrate --resample $samplerate -o \"$target\" \"$tempname2\"");
		unlink($tempname2);
	}
	else
	{
		if (($audioFormats[$index]['channels'] == 1) && ($audioFiles->list[$sourceindex]->channels == 2))
			$addparam = "--downmix";
		encodeWithOgg("$oggenc -b $bitrate -m $bitrate -M $bitrate --resample $samplerate $addparam -o \"$target\" \"$tempname1\"");
		unlink($tempname1);
	}
}
elseif (($audioFormats[$index]['format'] == 'mp3') && ($audioFiles->list[$sourceindex]->format == 'ogg'))
{
	$tempname1 = getTempWavName();
	decodeWithOgg("$oggdec -o \"$tempname1\" \"$source\"");
	if (($audioFormats[$index]['channels'] == 2) && ($audioFiles->list[$sourceindex]->channels == 1))
	{
		$tempname2 = getTempWavName();
		convertWithSox("$sox \"$tempname1\" -c2 \"$tempname2\"");
		unlink($tempname1);
		encodeWithLame("$lame --disptime 1 --cbr -b $bitrate -m $mode --resample $samplerate \"$tempname2\" \"$target\"");
		unlink($tempname2);
	}
	else
	{
		encodeWithLame("$lame --disptime 1 --cbr -b $bitrate -m $mode --resample $samplerate \"$tempname1\" \"$target\"");
		unlink($tempname1);
	}
}
elseif (($audioFormats[$index]['format'] == 'ogg') && ($audioFiles->list[$sourceindex]->format == 'ogg'))
{
	$tempname1 = getTempWavName();
	decodeWithOgg("$oggdec -o \"$tempname1\" \"$source\"");
	if (($audioFormats[$index]['channels'] == 2) && ($audioFiles->list[$sourceindex]->channels == 1))
	{
		$tempname2 = getTempWavName();
		convertWithSox("$sox \"$tempname1\" -c2 \"$tempname2\"");
		unlink($tempname1);
		encodeWithOgg("$oggenc -b $bitrate -m $bitrate -M $bitrate --resample $samplerate -o \"$target\" \"$tempname2\"");
		unlink($tempname2);
	}
	else
	{
		if (($audioFormats[$index]['channels'] == 1) && ($audioFiles->list[$sourceindex]->channels == 2))
			$addparam = "--downmix";
		encodeWithOgg("$oggenc -b $bitrate -m $bitrate -M $bitrate --resample $samplerate $addparam -o \"$target\" \"$tempname1\"");
		unlink($tempname1);
	}
}
$prg->setAudio($target);
endPage();

?>