<?php

require_once($config['getid3dir'] . "/getid3.php");
require_once("sotf_File.class.php");

/**
* This is a class for handling audio files.
*
* @author	Tamas Kezdi SZTAKI DSD <tbyte@sztaki.hu>
* @package	StreamOnTheFly
* @version	0.1
*/

class sotf_AudioFile extends sotf_File
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

    var $allInfo;

	/**
	* Sets up sotf_AudioFile object
	*
	* @constructor sotf_AudioFile
	* @param	string	$path	Path of the file

	*/

	function sotf_AudioFile($path, $fileinfo='')
	{
		global $config;
		
		$parent = get_parent_class($this);

		parent::$parent($path);		// Call the constructor of the parent class. lk. super()
		
		$oggaudio=false;
		$getid3_skipped=false;
		
		if(sotf_File::getExtension($this->path)=='ogg'){
			$getID3 = new getID3();
			$fileinfo = $getID3->analyze($this->path);
			getid3_lib::CopyTagsToComments($fileinfo);
			if(isset($fileinfo['audio']) && !isset($fileinfo['video'])) $oggaudio=true;
			else $getid3_skipped=true;
		}
		else if(!in_array(sotf_File::getExtension($this->path),$config['skipGetID3FileTypes'])){
			// CHANGED BY BUDDHAFLY 06-02-14
			$getID3 = new getID3();
			$fileinfo = $getID3->analyze($this->path);
			getid3_lib::CopyTagsToComments($fileinfo);
			
			//$fileinfo = GetAllFileInfo($this->path);
		}
		else $getid3_skipped=true;
		
		if(!$oggaudio && $getid3_skipped) $fileinfo['video']=true;
		
		if($fileinfo) $this->allInfo = $fileinfo; //was $fileInfo
		
		//if ($audioinfo["fileformat"] == 'mp3' || $audioinfo["fileformat"] == 'ogg') {

    //debug("finfo", $fileinfo);
	//print_r($fileinfo);

	// ADDED BY BUDDHAFLY 06-02-20
	if (isset ($fileinfo['video']) && $fileinfo['video']['dataformat']!="jpg") $this->type = "video";
	else if(isset($fileinfo['audio'])) $this->type = "audio";

    if (isset($fileinfo['audio'])) {

      $audioinfo = $fileinfo['audio'];

			// ADDED BY BUDDHAFLY 06-02-20
			if (isset ($fileinfo['video'])) $this->type = "video";
			else 
			// ADDED BY BUDDHAFLY 06-02-20
			$this->type = "audio";

			//$this->format = $fileinfo["fileformat"];
			$this->format = $fileinfo["fileformat"];

			if ($audioinfo["bitrate_mode"] == 'vbr')

				$this->bitrate = "VBR";

      $this->bitrate = round($audioinfo["bitrate"]/1000);

			$this->average_bitrate = round($audioinfo["bitrate"]/1000);

			$this->samplerate = $audioinfo["sample_rate"];

			$this->channels = $audioinfo["channels"];

			$this->duration = round($fileinfo["playtime_seconds"]);

			$this->mimetype = $this->determineMimeType($this->format);

		}

	} // end func sotf_AudioFile



	/**

	* Encode format to a filename.
	*
	* @return	string	Encoded format. Example: 24kbps_1chn_22050Hz.mp3
	* @use	$config['audioFormats'], $config['bitrateTolerance']
	*/

	function getFormatFilename()
	{

		global $config;

		global $config;



		$bitrate = $this->bitrate;

		for ($i=0;$i<count($config['audioFormats']);$i++)

			if (abs($config['audioFormats'][$i]['bitrate'] - $this->bitrate) < $config['bitrateTolerance'])

				$bitrate = $config['audioFormats'][$i]['bitrate'];

		return round($bitrate) . 'kbps_' . $this->channels . 'chn_' . $this->samplerate . 'Hz.' . $this->format;

	} // end func getFormatFilename



  /** static method converts format encoded into filename back to array of format characteristics. */

  function decodeFormatFilename($filename) {

   if(preg_match('/flash_preview.flv/', $filename)) return array('format' => 'flv');


    preg_match('/(\d+)kbps_(\d)chn_(\d+)Hz.(.*)/', $filename, $matches);

    return array('bitrate' => $matches[1],

                 'channels' => $matches[2],

                 'samplerate' => $matches[3],

                 'format' => $matches[4]);

  }


} // end class sotf_AudioFile