<?php

/**
* This is a class for checking the requiested formats
*
* @author	Tamas Kezdi SZTAKI DSD <tbyte@sztaki.hu>
* @package	StreamOnTheFly
* @version	0.1
*/
class sotf_AudioCheck
{
	/**
	* Array of the arrays describes which requestments can be satisfied.
	*
	* The description of the requestment can be found at the same index where the requestment was
	* found in the $audioFormats array. The first element of these arrays is a boolean indicates
	* whether the conditions are matched. If it is true the second elements of these arrays show
	* where is the matching file in the $list member variable. If the first element is false, the
	* second element can be an index shows from which file can be generated the requested file,
	* or it can be false if there was not so file. For example we need a 128 and a 24 kbps mp3
	* but we have only the 128. The first element will be (true,0) so we have the file at index 0,
	* the second element will be (false,0) so we haven't the 24 kbps file but it can be generated
	* from the 128 kbps file that has the index 0. If we needed a 192 kbps mp3, we got (false,false),
	* so we haven't so file, and we cannot generate it from 128, because 128 is less than 192.
	* @attribute 	array	$reqs
	* @see	{@link $audioFormats}
	*/
	var $reqs = array();

	/**
	* FileList object.
	*
	* @attribute 	object	$list
	* @see	{@link FileList}
	*/
	var $list;

	/**
	* Sets up the object
	*
	* @constructor sotf_AudioCheck
	* @param	object	$list	FileList object contains list of files to be checked
	* @use	$audioFormats
	*/
	function sotf_AudioCheck($list)
	{
		global $audioFormats;

		$this->list = & $list;
		for($i=0;$i<count($audioFormats);$i++)			// walk thru the requested formats
		{
			$found = false;								// indicates whether the current audio format has been found
			for($j=0;$j<count($this->list->list);$j++)	// walk thru the the files we have
			{
				// $this->list->list[$j] means the current AudioFile object
				if ($this->list->list[$j]->type != "audio")
					continue;							// This is not an audio, get another one
				if ($audioFormats[$i]['format'] != $this->list->list[$j]->format)
					continue;							// This is not the one we need, get another one
				if ($audioFormats[$i]['bitrate'] != $this->list->list[$j]->bitrate)
					continue;							// This is not the one we need, get another one
				if ($audioFormats[$i]['channels'] != $this->list->list[$j]->channels)
					continue;							// This is not the one we need, get another one
				if ($audioFormats[$i]['samplerate'] != $this->list->list[$j]->samplerate)
					continue;							// This is not the one we need, get another one
				$found = $j;							// All conditions matched, that's what we need, store its position
				break;									// don't need to search for another, leave the loop
			}
			if ($found !== false)
			{
				$this->reqs[] = array(true,$found);		// store the position of the matched file
				continue;								// get the next requested format
			}
			$found = false;								// indicates whether a better quality audio has been found
			for($j=0;$j<count($this->list->list);$j++)	// walk thru the the files again to get a better quality audio
			{
				if ($this->list->list[$j]->type != "audio")
					continue;							// This is not an audio, get another one
				if ($audioFormats[$i]['bitrate'] > $this->list->list[$j]->avarange_bitrate)
					continue;							// This is not the one we need, get another one
				if ($audioFormats[$i]['channels'] > $this->list->list[$j]->channels)
					continue;							// This is not the one we need, get another one
				if ($audioFormats[$i]['samplerate'] > $this->list->list[$j]->samplerate)
					continue;							// This is not the one we need, get another one
				$found = $j;							// Found a better one
				break;									// don't need to search for another, leave the loop
			}
			if ($found !== false)
			{
				$this->reqs[] = array(false,$found);	// store the position of the better quality file
				continue;								// get the next requested format
			}
			$this->reqs[] = array(false,false);			// There was nothing we could have use
		}
	} // end func sotf_AudioCheck

	/**
	* Gets the request index for a sotf_AudioFile
	*
	* @param	object	$audiofile	sotf_AudioFile object to be checked
	* @return	mixed	If the audio file satisfies any requestment returns an integer which is an index of the $audioFormats global variable. If the file satisfy any requestment returns boolean false
	* @use	$audioFormats
	*/
	function getRequestIndex($audiofile)
	{
		global $audioFormats;

		for($i=0;$i<count($audioFormats);$i++)			// walk thru the requested formats
		{
			if ($audiofile->type != "audio")
				continue;								// This is not an audio, get another one
			if ($audioFormats[$i]['format'] != $audiofile->format)
				continue;								// This is not the one we need, get another one
			if ($audioFormats[$i]['bitrate'] != $audiofile->bitrate)
				continue;								// This is not the one we need, get another one
			if ($audioFormats[$i]['channels'] != $audiofile->channels)
				continue;								// This is not the one we need, get another one
			if ($audioFormats[$i]['samplerate'] != $audiofile->samplerate)
				continue;								// This is not the one we need, get another one
			return $j;									// All conditions matched, that's what we need, return the index
		}
		return false;
	} // end func getRequestIndex

	/**
	* Encode format to a filename.
	*
	* @param	integer	$index	Format index of the jingle in the $audioFormats global variable
	* @return	string	Encoded format. Example: 24kbps_1chn_22050Hz.mp3
	* @use	$audioFormats
	*/
	function getFormatFilename($index)
	{
		global $audioFormats;

		return $audioFormats[$index]['bitrate'] . 'kbps_' . $audioFormats[$index]['channels'] . 'chn_' . $audioFormats[$index]['samplerate'] . 'Hz.' . $audioFormats[$index]['format'];
	} // end func getFormatFilename

} // end class sotf_AudioCheck

?>