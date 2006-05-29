<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

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

function endPage($file_errors=0)
{
	echo "<script>\n";
	
	echo "alert('Convert ready!";
	if($file_errors==1) echo "\\n 1 file has not successfully been converted";
	elseif($file_errors) echo "\\n $file_errors files has not successfully been converted";
	echo "');\n";
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
	global $config;

	$tempname = tempnam($config['tmpDir'],"__");
	unlink($tempname);
	return $tempname. ".wav";
}

function progressBar($cmd,$regexp)
{
	global $config;

	$line = "";
	$out = 0;
	$left = $config['progressBarLength'];

	debug('execute',$cmd);
	$fp = popen($cmd . ' 2>&1', 'r');
	while(!feof($fp))
	{
		$data = fread($fp,1);
		
		if ((ord($data) == 13) || (ord($data) == 10))
		{
			if (preg_match($regexp,$line,$match))
			{
				$curr = (integer) (((integer) $match[1]) * $config['progressBarLength'] / 100);
				for ($i=$out;$i<$curr;$i++)
				{
					echo $config['progressBarChar'];
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
		echo $config['progressBarChar'];
		$left--;
		$out++;
	}
	flush();
}



function encodeWithLame($cmd)
{
	global $config;

	echo "<p>Encoding MP3 file...<br />\n";
	flush();

	progressBar($cmd,$config['lameencRegexp']);
	echo "</p>\n";
	flush();
}

function decodeWithLame($cmd)
{
	global $config;

	echo "<p>Decoding MP3 file to PCM data...<br />\n";
	flush();
	debug('execute',$cmd);
	$result = exec($cmd);
  debug('result',$result);
	for ($i=0;$i<$config['progressBarLength'];$i++)
		echo $config['progressBarChar'];
	echo "</p>\n";
	flush();
}

function encodeWithOgg($cmd)
{
	global $config;

	echo "<p>Encoding OGG file...<br />\n";
	flush();
	progressBar($cmd,$config['oggencRegexp']);
	echo "</p>\n";
	flush();
}

function decodeWithOgg($cmd)
{
	global $config;

	echo "<p>Decoding OGG file to PCM data...<br />\n";
	flush();
	debug('execute',$cmd);
	exec($cmd);
	for ($i=0;$i<$config['progressBarLength'];$i++)
		echo $config['progressBarChar'];
	echo "</p>\n";
	flush();
}

function convertWithSox($cmd)
{
	global $config;

	echo "<p>Convert mono PCM data to stereo...<br />\n";
	flush();
	debug('execute',$cmd);
	exec($cmd);
	for ($i=0;$i<$config['progressBarLength'];$i++)
		echo $config['progressBarChar'];
	echo "</p>\n";
	flush();
}

function transcodeWithFfmpeg($cmd) //ADDED BY BUDDHAFLY
{
	global $config;

	echo "<p>Encoding Video file...<br />\n";
	flush();

	progressBar($cmd,$config['ffmpegRegexp']);
	echo "</p>\n";
	flush();
}


function checkFile($file) {
  if(!is_readable($file)) {
	 raiseError("conversion_failed");
  }
}

function rmFile($file) {
  unlink($file) or logError("Could not delete file: $file");
}

function fileOK($file) {
	global $config;

	if(!in_array(sotf_File::getExtension($file),$config['skipGetID3FileTypes'])){
		$getID3 = new getID3();
		$fileinfo = $getID3->analyze($file);
		getid3_lib::CopyTagsToComments($fileinfo);
	}
	else $fileinfo['video']=true;
	
  if(!is_readable($file) || filesize($file)==0 || !isset($fileinfo['audio'])) {
	return false;
  }
	return true;
}


$id = sotf_Utils::getParameter('id'); 
$index = sotf_Utils::getParameter('index'); 
$jingle = sotf_Utils::getParameter('jingle'); 
$all = sotf_Utils::getParameter('all');


$obj = $repository->getObject($id);

if(!$obj)
	  raiseError("object does not exist!");
checkPerm($obj->id, 'change');


// CHANGED BY BUDDHAFLY
$audioVideoFiles = & new sotf_FileList(); 
if($jingle) {
  $audioVideoFiles->getAudioVideoFromDir($obj->getMetaDir());
} else {
  $audioVideoFiles->getAudioVideoFromDir($obj->getAudioDir()); 
}
$checker = & new sotf_ContentCheck($audioVideoFiles);
$checker = $checker->selectType(); 
if($videoconv) $checker->console = false;
else $checker->console = true;
////////////////////////////////////////////////////////

startPage();

$file_errors = 0;

if($all) { //convertall
	$targets = $checker->convertAll($obj->id);
	
	if($videoconv){//if video
		foreach($targets as $target) {
			if(!$checker->fileOK($target)) {
				$file_errors++;
				continue;
			}
			if($jingle) {
				$obj->setJingle($target);
			} 
			else{
				$obj->setAudio($target);
			}
		}
	} // if video
	
	else{ //if audio
		foreach($targets as $target) {
			if($jingle) {
				$obj->setJingle($target);
			} 
			else {
				$obj->setAudio($target);
			}
		}
	} //if audio
	
} //convertall

else { //convert id
	$target = $checker->convert($obj->id, $index);
	
	if($videoconv){ //if video
		if($checker->fileOK($target)){
			if($jingle) {
				$obj->setJingle($target);
			} 
			else{
				$obj->setAudio($target);
			}
		}
		else $file_errors++;
	} //if video
	
	else{ //if audio
		if($jingle) {
			$obj->setJingle($target);
		} 
		else {
			$obj->setAudio($target);
		}
	}  //if audio

} //convert id


endPage($file_errors);

?>