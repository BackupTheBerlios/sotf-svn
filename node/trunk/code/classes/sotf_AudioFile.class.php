<?php

require_once($getid3dir . "/getid3.php");
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
	* Avarange bitrate of the audio file in kbps.
	* 
	* @attribute 	float	$avarange_bitrate
	*/
	var $avarange_bitrate;

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

	/**
	* Sets up sotf_AudioFile object
	*
	* @constructor sotf_AudioFile
	* @param	string	$path	Path of the file
	*/
	function sotf_AudioFile($path)
	{
		$parent = get_parent_class($this);
		parent::$parent($path);		// Call the constructor of the parent class. lk. super()
		$audioinfo = GetAllMP3info($this->path);
		if ($audioinfo["fileformat"] == 'mp3' || $audioinfo["fileformat"] == 'ogg')
		{
			$this->type = "audio";
			$this->format = $audioinfo["fileformat"];
			if ($audioinfo["bitrate_mode"] == 'vbr')
				$this->bitrate = "VBR";
			else
				$this->bitrate = $audioinfo["bitrate_audio"]/1000;
			if (!$this->bitrate)
				$this->bitrate = $audioinfo["bitrate"]/1000;
			if (!$this->bitrate && $this->format == 'mp3')
				$this->bitrate = $audioinfo["mpeg"]["audio"]["bitrate"]/1000;
			$this->avarange_bitrate = $audioinfo["bitrate_audio"]/1000;
			if (!$this->avarange_bitrate && is_numeric($this->bitrate))
				$this->avarange_bitrate = $this->bitrate;
			$this->samplerate = $audioinfo["frequency"];
			if (!$this->samplerate && $this->format == 'mp3')
				$this->samplerate = $audioinfo["mpeg"]["audio"]["frequency"];
			$this->channels = $audioinfo["channels"];
			if (!$this->channels && $this->format == 'mp3')
				$this->channels = $audioinfo["mpeg"]["audio"]["channels"];
			$this->duration = $audioinfo["playtime_seconds"];
			$this->mimetype = $this->determineMimeType($this->format);
		}
	} // end func sotf_AudioFile

	/**
	* Encode format to a filename.
	*
	* @return	string	Encoded format. Example: 24kbps_1chn_22050Hz.mp3
	*/
	function getFormatFilename()
	{
		return $this->bitrate . 'kbps_' . $this->channels . 'chn_' . $this->samplerate . 'Hz.' . $this->format;
	} // end func getFormatFilename

  /** static method converts format encoded into filename back to array of format characteristics. */
  function decodeFormatFilename($filename) {
    preg_match('/(\d+)kbps_(\d)chn_(\d+)Hz.(.*)/', $filename, $matches);
    return array('bitrate' => $matches[1],
                 'channels' => $matches[2],
                 'samplerate' => $matches[3],
                 'format' => $matches[4]);
  }


} // end class sotf_AudioFile