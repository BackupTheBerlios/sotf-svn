<?php

require_once($config['getid3dir'] . "/getid3.php");
require_once("sotf_File.class.php");

/**
* This is a class for handling audio files.
* MODIFIED FOR HANDLING VIDEO FILES BY Martin Schmidt
* @author	Tamas Kezdi SZTAKI DSD <tbyte@sztaki.hu>
* @package	StreamOnTheFly
* @version	0.1
*/

class sotf_VideoFile extends sotf_File
{

	/**
	* Format of the audio file.
	* 
	* Currently this member variable can be "mp3" or "ogg".
	* @attribute 	string	$format
	*/

	var $format;

	/**
	* Bitrate of the audio file.
	* 
	* If this value is numeric, it contains the constant bit rate in kbps, or it can be "VBR".
	* @attribute 	mixed	$bitrate
	*/

	var $bitrate;

	/**
	* Average bitrate of the audio file in kbps.
	* 
	* @attribute 	float	$average_bitrate
	*/

	var $average_bitrate;

	/**
	* Sample rate of the audio file in Hz.
	* @attribute 	integer	$samplerate
	*/

	var $samplerate;

	/**
	* Number of channels.
	*
	* 1 means this is mono, 2 means this is stereo
	* @attribute 	integer	$channels
	*/

	var $channels;

	/**
	* Duration of the audio file in seconds.
	*
	* @attribute 	float	$duration
	*/

	var $duration;
	
	var $codec;
	
	var $frame_rate;
	
	var $resolution_x;
	
	var $resolution_y;
	
	var $pixel_aspect_ratio;

    var $allInfo;
	
	var $totalframes;

	/**
	* Sets up sotf_AudioFile object
	*
	* @constructor sotf_AudioFile
	* @param	string	$path	Path of the file
	*/

	function sotf_VideoFile($path, $fileinfo='')
	{
		global $config;
		
		$parent = get_parent_class($this);

		parent::$parent($path);		// Call the constructor of the parent class. lk. super()
		
		if(!in_array(sotf_File::getExtension($path),$config['skipGetID3FileTypes'])){
			// CHANGED BY BUDDHAFLY 06-02-14
			$getID3 = new getID3();
			$fileinfo = $getID3->analyze($path);
			getid3_lib::CopyTagsToComments($fileinfo);
			//print_r_html($fileinfo);
			//$fileinfo = GetAllFileInfo($this->path);
		}
		else $fileinfo['video']=true;
		 
   		 if($fileinfo) $this->allInfo = $fileinfo; //was $fileInfo

		//if ($audioinfo["fileformat"] == 'mp3' || $audioinfo["fileformat"] == 'ogg') {

    //debug("finfo", $fileinfo);
	

    if(!in_array(sotf_File::getExtension($path),$config['skipGetID3FileTypes'])) {

     	 $videoinfo = $fileinfo['video'];

			$this->type = "video";

			//$this->format = $fileinfo["fileformat"];

			$this->format = $videoinfo["dataformat"];
			
			if($this->format == "quicktime") $this->format = "mov";
			
			if($fileinfo['quicktime']['ftyp']['signature']=="3gp4") $this->format = "3gp";
			else if($this->format == "mpeg4") $this->format = "mp4";
			
			if($this->format == "asf") $this->format = "wmv";
			if($this->format == "mpeg") $this->format = "mpg";
			
			
			if ($videoinfo["bitrate_mode"] == 'vbr') $this->bitrate = "VBR";

      		$this->bitrate = round($fileinfo["bitrate"]/1000);

			$this->average_bitrate = round($fileinfo["bitrate"]/1000);


			
			$this->duration = round($fileinfo["playtime_seconds"]);

			$this->mimetype = $this->determineMimeType($this->format);
			
			$this->codec = $videoinfo["codec"];
	
			$this->frame_rate = round($videoinfo["frame_rate"]);
	
			$this->resolution_x = $videoinfo["resolution_x"];
	
			$this->resolution_y = $videoinfo["resolution_y"];
			
			$this->lossless = $videoinfo["lossless"];
	
			$this->pixel_aspect_ratio = $videoinfo["pixel_aspect_ratio"];
			
			
			if(isset($fileinfo['audio'])){
			 $audioinfo = $fileinfo['audio'];
			 if($audioinfo["sample_rate"])$this->samplerate = $audioinfo["sample_rate"];
			 else $this->samplerate = 0;
			 if($audioinfo["channels"]) $this->channels = $audioinfo["channels"];
			 else $this->channels = 0;
			}
			else {
			$this->samplerate = 0;
			$this->channels = 0;
			
			}

		}
		
		else if (isset($fileinfo['video'])){
			$this->type = "video";
			$this->format = sotf_File::getExtension($path);
			$this->mimetype = $config['mimetypes']['format'];
			$this->bitrate = 0;
			$this->samplerate = 0;
			$this->channels = 0;
			
		}

	} // end func sotf_AudioFile

	
	
	function createStills($file, $length, $id){
	
		global $config;

		
		$temppath=$config['wwwdir']."/tmp";
		
		for($i=1;$i<=5;$i++){
			$position = round((($i+($i-1))/10)*$length);
			$target = $temppath."/still_".$id."_".$i.".gif";
			$cmd = "nohup nice -n 15 ".$config['ffmpeg']." -i $file ".$config['ffmpeg_params_stills']."  -ss $position -y $target 1>$target.txt 2>&1 &";
			exec($cmd);
		}
		
	}


	
	function searchForStill($prg){
		
		$id=$prg->get('id');
		
		global $config;
		$temppath=$config['wwwdir']."/tmp/";
		
		$still_found=false;
		
		if ($directory = opendir($prg->getOtherFilesDir())) {
		   while (false !== ($filename = readdir($directory))) {
				if(preg_match("/^still_".$id."_[12345]\.gif$/",$filename)){
					$still_found=true;
			   }
		   }
		   closedir($directory);
		}
		
		if ($tempdir = opendir($temppath)) {
		   while (false !== ($filename = readdir($tempdir))) {
				if(preg_match("/^still_".$id."_[12345]\.gif$/",$filename)){
					if(!isUnicolorImage($temppath.$filename)){
						$still_found=true;
					}
			   }
		   }
		   closedir($tempdir);
		}
		
		return $still_found;
	}
	
	
	function processTranscodingQueue($repository, $prg){
	
		global $config;
		
		$id=$prg->get('id');
		
		
		$temppath=$config['wwwdir']."/tmp/";
		$list_changed = false;
		
		if ($tempdir = opendir($temppath)) {
		   while (false !== ($filename = readdir($tempdir))) {
				if(preg_match("/^".$id."_/",$filename)){
					if(sotf_VideoCheck::fileOK($temppath.$filename)) {
						if(is_file($temppath.$filename.".txt")) unlink($temppath.$filename.".txt");
						$prg->setAudio($temppath.$filename);
						$list_changed=true;
					}
				
				}if(preg_match("/^still_".$id."_[12345]\.gif$/",$filename)){
					if(!isUnicolorImage($temppath.$filename)){ 
						$obj_id=$prg->setOtherFile($temppath.$filename);
						$fileInfo = &$repository->getObject($obj_id);
						$fileInfo->set('public_access', 'f');
						$fileInfo->update();
						if(is_file($temppath.$filename.".txt")) unlink($temppath.$filename.".txt");
	
					}
					else{
						if(is_file($temppath.$filename.".txt")) unlink($temppath.$filename.".txt");
						unlink($temppath.$filename);
					}
					$list_changed=true;
			   }
		   }
		   closedir($tempdir);
		}
		
		return $list_changed;
	}
	
	

	/**

	* Encode format to a filename.
	*
	* @return	string	Encoded format. Example: 24kbps_1chn_22050Hz.mp3
	* @use	$config['audioFormats'], $config['bitrateTolerance']
	*/

	function getFormatFilename()
	{

		global $config;
		
		if($this->format!='flv'){

			$bitrate = $this->bitrate;
	
			for ($i=0;$i<count($config['videoFormats']);$i++)
	
				if (abs(($config['videoFormats'][$i]['audio_bitrate']+$config['videoFormats'][$i]['video_bitrate']) - $this->bitrate) < $config['bitrateTolerance'])
	
					$bitrate = $config['videoFormats'][$i]['audio_bitrate']+$config['videoFormats'][$i]['video_bitrate'];
					
			return round($bitrate) . 'kbps_' . $this->channels . 'chn_' . $this->samplerate . 'Hz.'. $this->format;
		
		}
		
		else return "flash_preview.flv";
		

	} // end func getFormatFilename



  /** static method converts format encoded into filename back to array of format characteristics. */

  function decodeFormatFilename($filename) {

	if(preg_match('/flash_preview.flv/', $filename)) return array('format' => 'flv');
	
    preg_match('/(\d+)kbps_(\d)chn_(\d+)Hz.(.*)/', $filename, $matches);

    return array('bitrate' => $matches[1],

                 'channels' => $matches[2],

                 'samplerate' => $matches[3],
				 
                 'format' => $matches[5]);

  }
  

} // end class sotf_AudioFile