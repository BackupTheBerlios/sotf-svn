<?php


function print_r_html($arr, $style = "display: none; margin-left: 10px;")
{ 
//print_r($arr);
//return;

static $i = 0; $i++;
  echo "\n<div id=\"array_tree_$i\" class=\"array_tree\">\n";
  foreach($arr as $key => $val)
  { switch (gettype($val))
   { case "array":
       echo "<a onclick=\"document.getElementById('array_tree_element_$i').style.display = ";
       echo "document.getElementById('array_tree_element_$i";
       echo "').style.display == 'block' ?";
       echo "'none' : 'block';\"\n";
       echo "name=\"array_tree_link_$i\" href=\"#array_tree_link_$i\">".htmlspecialchars($key)."</a><br />\n";
       echo "<div class=\"array_tree_element_\" id=\"array_tree_element_$i\" style=\"$style\">";
       echo print_r_html($val);
       echo "</div>";
     break;
     case "integer":
       echo "<b>".htmlspecialchars($key)."</b> => <i>".htmlspecialchars($val)."</i><br />";
     break;
     case "double":
       echo "<b>".htmlspecialchars($key)."</b> => <i>".htmlspecialchars($val)."</i><br />";
     break;
     case "boolean":
       echo "<b>".htmlspecialchars($key)."</b> => ";
       if ($val)
       { echo "true"; }
       else
       { echo "false"; }
       echo  "<br />\n";
     break;
     case "string":
       echo "<b>".htmlspecialchars($key)."</b> => <code>".htmlspecialchars($val)."</code><br />";
     break;
     default:
       echo "<b>".htmlspecialchars($key)."</b> => ".gettype($val)."<br />";
     break; }
   echo "\n"; }
  echo "</div>\n"; }


/**
* This is a class for checking the requested VIDEO formats, and for converting them
*
* @author	Tamas Kezdi SZTAKI DSD <tbyte@sztaki.hu>
* @package	StreamOnTheFly
* @version	0.1
*/

class sotf_VideoCheck extends sotf_ContentCheck
{

	

	/**
	* Array of the arrays describes which requestments can be satisfied.
	*
	* The description of the requestment can be found at the same index where the requestment was
	* found in the $config['videoFormats'] array. The first element of these arrays is a boolean indicates
	* whether the conditions are matched. If it is true the second elements of these arrays show
	* where is the matching file in the $list member variable. If the first element is false, the
	* second element can be an index shows from which file can be generated the requested file,
	* or it can be false if there was not so file. For example we need a 128 and a 24 kbps mp3
	* but we have only the 128. The first element will be (true,0) so we have the file at index 0,
	* the second element will be (false,0) so we haven't the 24 kbps file but it can be generated
	* from the 128 kbps file that has the index 0. If we needed a 192 kbps mp3, we got (false,false),
	* so we haven't so file, and we cannot generate it from 128, because 128 is less than 192.
	* @attribute 	array	$reqs
	* @see	{@link $config['videoFormats']}
	*/

	var $reqs = array();

	/**
	* FileList object.
	*
	* @attribute 	object	$list
	* @see	{@link FileList}
	*/

	var $list;

	var $prefix = 'video';
	var $type = 'videoChecker';

  var $console = false;

	/**
	* Sets up the object
	*
	* @constructor sotf_AudioCheck
	* @param	object	$list	FileList object contains list of files to be checked
	* @use	$config['videoFormats'], $config['bitrateTolerance']
	*/
	

	function sotf_VideoCheck($list)
	{
		global $config;
		
		$display_arr=false;
		
		$this->list = & $list;

		for($i=0;$i<count($config['videoFormats']);$i++)			// walk thru the requested formats

		{
			$found = false;								// indicates whether the current audio format has been found
			for($j=0;$j<count($this->list->list);$j++)	// walk thru the the files we have
			{

				// $this->list->list[$j] means the current AudioFile object
				if ($this->list->list[$j]->type != "video"){
					continue;							// This is not an video, get another one
				}

				if ($config['videoFormats'][$i]['format'] != $this->list->list[$j]->format){
					if($display_arr) print "$i$j wrong format: ".$this->list->list[$j]->format.", should be ".$config['videoFormats'][$i]['format']."<br>".print_r_html($this->list->list[$j])."<br>&nbsp;<br>";
					continue;							// This is not the one we need, get another one
				}

				
				/*if($this->list->list[$j]->format!="flv"){
					if (abs($this->list->list[$j]->average_bitrate - ($config['videoFormats'][$i]['video_bitrate']+$config['videoFormats'][$i]['audio_bitrate'])) > $config['bitrateToleranceVideo']){
						if($display_arr) print "$i$j wrong bitrate: ".$this->list->list[$j]->average_bitrate.", should be ".($config['videoFormats'][$i]['video_bitrate']+$config['videoFormats'][$i]['audio_bitrate'])."<br>".print_r_html($this->list->list[$j])."<br>&nbsp;<br>";
						continue;							// This is not the one we need, get another one
					}
	
					else
	
					{
						$this->list->list[$j]->bitrate = $config['videoFormats'][$i]['video_bitrate']+$config['videoFormats'][$i]['audio_bitrate'];
						$this->list->list[$j]->average_bitrate = $config['videoFormats'][$i]['video_bitrate']+$config['videoFormats'][$i]['audio_bitrate'];
					}
	
					if ($config['videoFormats'][$i]['audio_channels'] != $this->list->list[$j]->channels){
						if($display_arr) print "$i$j wrong channels: ".$this->list->list[$j]->channels.", should be ".$config['videoFormats'][$i]['audio_channels']."<br>".print_r_html($this->list->list[$j])."<br>&nbsp;<br>";
						continue;							// This is not the one we need, get another one
					}
				}*/

				if ($config['videoFormats'][$i]['audio_samplerate'] != $this->list->list[$j]->samplerate){
					if($display_arr) print "$i$j wrong samplerate: ".$this->list->list[$j]->samplerate.", should be ".$config['videoFormats'][$i]['audio_samplerate']."<br>".print_r_html($this->list->list[$j])."<br>&nbsp;<br>";
					continue;							// This is not the one we need, get another one
				}

				$found = $j;							// All conditions matched, that's what we need, store its position
				break;									// don't need to search for another, leave the loop

			}

			if ($found !== false)
			{
				$this->reqs[] = array(true,$found);		// store the position of the matched file
				continue;								// get the next requested format
			}

			$this->reqs[] = array(false,false);			// There was nothing we could have use
		}
	} // end func sotf_AudioCheck


	/**
	* Gets the request index for a sotf_AudioFile
	*
	* @param	object	$audiofile	sotf_AudioFile object to be checked
	* @return	mixed	If the audio file satisfies any requestment returns an integer which is an index of the $config['videoFormats'] global variable. If the file satisfy any requestment returns boolean false
	* @use	$config['videoFormats'], $config['bitrateTolerance']
	*/

	function getRequestIndex($videofile)
	{
		global $config;
		for($i=0;$i<count($config['videoFormats']);$i++)			// walk thru the requested formats

		{
			if ($videofile->type != "video")
				continue;								// This is not an audio, get another one

			if ($config['videoFormats'][$i]['format'] != $videofile->format)
				continue;								// This is not the one we need, get another one

			//if($videofile->format!="flv"){
				if (abs($videofile->average_bitrate - $config['videoFormats'][$i]['video_bitrate']+$videofile->average_bitrate - $config['videoFormats'][$i]['audio_bitrate']) > $config['bitrateToleranceVideo'])
					continue;							// This is not the one we need, get another one
	
				if ($config['videoFormats'][$i]['audio_channels'] != $videofile->channels)
					continue;								// This is not the one we need, get another one
			//}
			if ($config['videoFormats'][$i]['audio_samplerate'] != $videofile->samplerate)
				continue;								// This is not the one we need, get another one

			return $j;									// All conditions matched, that's what we need, return the index

		}

		return false;

	} // end func getRequestIndex




	/**
	* Encode format to a filename.
	*
	* @param	integer	$index	Format index of the jingle in the $config['audioFormats'] global variable
	* @return	string	Encoded format. Example: 24kbps_1chn_22050Hz.mp3
	* @use	$config['audioFormats']
	*/

	function getFormatFilename($index)

	{

		global $config;

		return ($config['videoFormats'][$index]['video_bitrate']+$config['videoFormats'][$index]['audio_bitrate']) . 'kbps_' . $config['videoFormats'][$index]['audio_channels'] . 'chn_' . $config['videoFormats'][$index]['audio_samplerate'] . 'Hz.' . $config['videoFormats'][$index]['format'];
		
		

	} // end func getFormatFilename



	
	
function fileOK($file) {

	$getID3 = new getID3();
	$fileinfo = $getID3->analyze($file);
	getid3_lib::CopyTagsToComments($fileinfo);
	
	if(is_file($file.".txt")){
	
		$handle = fopen ($file.".txt", "r");
		$buffer="";
		while (!feof($handle)) {
		   $buffer .= fgets($handle, 4096);
		}
		fclose ($handle);
		$finished = stristr($buffer,'muxing overhead');

	}
	else $finished = false;
	
	if(!is_readable($file) || filesize($file)==0 || (!isset($fileinfo['audio'])&&!isset($fileinfo['video'])) || !$finished) {
		return false;
	}
	else{
		
		return true;
	}
}

	function getTotalFrames($source, $index){
		
		global $config;
		
		$getID3 = new getID3();
		$fileinfo = $getID3->analyze($source);
		getid3_lib::CopyTagsToComments($fileinfo);
		
		$totalframes=round($fileinfo["playtime_seconds"]*$config['videoFormats'][$index]['framerate']);
		
		return $totalframes;
	}
	
	function getPercentageOrError($tempfile, $totalframes){
	
		if(is_file($tempfile.".txt")){
			$handle = fopen ($tempfile.".txt", "r");
			$buffer="";
			while (!feof($handle)) {
			   $buffer .= fgets($handle, 4096);
			}
			fclose ($handle);
			
			$returnarray=array();			
	
			preg_match_all("/frame=(.{1,9})q=/", $buffer, $results);
			$curframe=$results[1][count($results[1])-1];
			$timediff= time()-filemtime($tempfile);

			if (empty($results[1]) && is_file($tempfile) && preg_match_all("/\n\[.*@ 0x.*\n/", $buffer, $errors) && $timediff>3){
				$returnarray['errors']=$errors[0];
				logError('conversion failed: '.$tempfile);
				logError('ffmpeg output: '. $buffer); 
			}
			$percentage=round($curframe/$totalframes*100);
			$returnarray['percentage']=$percentage;
			return $returnarray;
		}
	}



  ////////////////////////////////////////////////////////
  //
  //  VIDEO CONVERSION
  //
  ///////////////////////////////////////////////////////


  
    function transcodeWithFfmpeg($cmd, $totalframes)
      {

		global $config, $page;

        if($this->console) {
          //echo "<p>"; // . $page->getlocalized('transcoding_video') . "<br />\n";
          flush();
          
          $output = $this->progressBar($cmd,$config['ffmpegRegexp'], $totalframes, true, $config['ffmpegErrorRegexp']);
          echo "<br/>\n";
          flush();
		  return $output;
        } else {
          $this->exec($cmd);
        }
		

      }

  
  
    /** returns name of new audio file */

    function convert($id, $index) {

      global $config, $page;

      debug('conversion started', $this->getFormatFilename($index));

      if ($this->reqs[$index][0] === true) {
        debug("We have this format already", $index);
        return;
      }

      $sourceindex = $this->reqs[$index][1];


      $audioFiles = & $this->list;
      $source = $audioFiles->list[$sourceindex]->getPath();
	  
      debug("source", $source);

      $target = $config['tmpDir'] . '/' . $id . '_' . time() . '_' . $this->getFormatFilename($index);


      $bitrate = $config['videoFormats'][$index]["audio_bitrate"];
      $samplerate = $config['videoFormats'][$index]["audio_samplerate"];
      if ($config['videoFormats'][$index]["audio_channels"] == 1)
        $mode = "mono";
      else
        $mode = "joint";
	
		$this->getTotalFrames($source, $index);

		
		$cmd="nohup nice -n 15 ".$config['ffmpeg'].' -i '.$source.' '.$config['videoFormats'][$index]['ffmpeg_params']./*' -minrate '. ($config['videoFormats'][$index]['video_bitrate']) .' -maxrate '. ($config['videoFormats'][$index]['video_bitrate']+$config['bitrateToleranceVideo']) .' -bufsize 4096 '.*/' '.$target." 1>".$target.".txt 2>&1 &";
		
		
      if($this->console){
        echo "<br>" . $page->getlocalized('conversion_started') . ' ' . $this->getFormatFilename($index) . "<br>\n";
		$output=$this->transcodeWithFfmpeg($cmd, $totalframes);
	  }
           $this->transcodeWithFfmpeg($cmd, $totalframes);
		  
       /*
	   $videoFileOK = $this->fileOK($target);
	   	if(!$videoFileOK) {
			logError('conversion failed: '.$target);
			if($output)logError('ffmpeg output: '. $output['data']); 
			if($this->console){
				echo "<span style='color:#c00'>[ffmpeg error] Transcoding failed. The error has been logged.</span>";
				echo "<br>&nbsp;";
			}
		}
		else unlink($target.".txt");
		*/
		
		
      debug('conversion finished', $target);

      return $target;
    }

} // end class sotf_AudioCheck

?>