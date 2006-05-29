<?php


/**
* This is a class for checking the requested VIDEO formats, and for converting them
*
* @author	Martin Schmidt <ptmschmidt@fh-stpoelten.ac.at>
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

				$found = $j;							// That's what we need, store its position
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
	* @use	$config['videoFormats']
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

			if(!in_array(sotf_File::getExtension($path),$config['skipGetID3FileTypes'])){
				if (abs($videofile->average_bitrate - $config['videoFormats'][$i]['video_bitrate']+$videofile->average_bitrate - $config['videoFormats'][$i]['audio_bitrate']) > $config['bitrateToleranceVideo'])
					continue;							// This is not the one we need, get another one
	
				if ($config['videoFormats'][$i]['audio_channels'] != $videofile->channels)
					continue;								// This is not the one we need, get another one
			
				if ($config['videoFormats'][$i]['audio_samplerate'] != $videofile->samplerate)
					continue;								// This is not the one we need, get another one
			}
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
		
		if($config['videoFormats'][$index]['format']!='flv'){
			return ($config['videoFormats'][$index]['video_bitrate']+$config['videoFormats'][$index]['audio_bitrate']) . 'kbps_' . $config['videoFormats'][$index]['audio_channels'] . 'chn_' . $config['videoFormats'][$index]['audio_samplerate'] . 'Hz.' . $config['videoFormats'][$index]['format'];
		}
		
		else return "flash_preview.flv";
		
		

	} // end func getFormatFilename



	
	
function fileOK($file) {

	global $config;

	if(!in_array(sotf_File::getExtension($file),$config['skipGetID3FileTypes'])){
		$getID3 = new getID3();
		$fileinfo = $getID3->analyze($file);
		getid3_lib::CopyTagsToComments($fileinfo);
	}
	else $fileinfo['video']=true;
	
	if(is_file($file.".txt")){
	
		$handle = fopen ($file.".txt", "r");
		$buffer="";
		while (!feof($handle)) {
		   $buffer .= fgets($handle, 4096);
		}
		fclose ($handle);
		$finished = stristr($buffer,$config['ffmpegFinishMessage']) && !stristr($buffer, $config['ffmpegEmptyVideo']);

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
		if(!in_array(sotf_File::getExtension($source),$config['skipGetID3FileTypes'])){
			$getID3 = new getID3();
			$fileinfo = $getID3->analyze($source);
			getid3_lib::CopyTagsToComments($fileinfo);
			$totalframes=round($fileinfo["playtime_seconds"]*$config['videoFormats'][$index]['framerate']);
					return $totalframes;
		}
		else return -1;

	}
	
	function getPercentageOrError($tempfile, $totalframes){
	
		global $config;
	
		if(is_file($tempfile.".txt")){
			$handle = fopen ($tempfile.".txt", "r");
			$buffer="";
			while (!feof($handle)) {
			   $buffer .= fgets($handle, 4096);
			}
			fclose ($handle);
			
			$returnarray=array();			
	
			preg_match_all($config['ffmpegRegexp'], $buffer, $results);
			$curframe=$results[1][count($results[1])-1];
			$timediff= time()-filemtime($tempfile);
			
			$errors_before=false;
			$errors_during=false;
			
			for($i=0;$i<count($config['ffmpegErrorsBeforeConversion']);$i++){
				if(preg_match_all($config['ffmpegErrorsBeforeConversion'][$i], $buffer, $errors_1)) {
					$errors_before=true;
				}
			}
			
			for($j=0;$j<count($config['ffmpegErrorsDuringConversion']);$j++){
				if(preg_match_all($config['ffmpegErrorsDuringConversion'][$j], $buffer, $errors_2)) {
					$errors_during=true;
				}
			}

			if (is_file($tempfile) && ((empty($results[1]) && $errors_before) || $errors_during) && $timediff>3)
			{
				$errors=array_merge($errors_1, $errors_2);
				$returnarray['errors']=$errors;
				logError('conversion failed: '.$tempfile);
				logError('ffmpeg output: '. $buffer); 
			}
			$percentage='';
			if($totalframes!=-1) @$percentage=round($curframe/$totalframes*100);
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
          flush();
          
          $output = $this->progressBar($cmd,$config['ffmpegRegexp'], $totalframes, true);
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

		
		$cmd="nohup nice -n 15 ".$config['ffmpeg'].' -i '.$source.' '.$config['videoFormats'][$index]['ffmpeg_params'].' '.$target." 1>".$target.".txt 2>&1 &";
		
		
      if($this->console){
        echo "<br>" . $page->getlocalized('conversion_started') . ' ' . $this->getFormatFilename($index) . "<br>\n";
		$output=$this->transcodeWithFfmpeg($cmd, $totalframes);
	  }
           $this->transcodeWithFfmpeg($cmd, $totalframes);
		  
		
      debug('conversion finished', $target);

      return $target;
    }

} // end class sotf_AudioCheck

?>