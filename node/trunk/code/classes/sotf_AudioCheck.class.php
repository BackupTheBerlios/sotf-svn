<?php

/**
* This is a class for checking the requested formats, and for converting them
* So this is a bad class name, should be AudioTools
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
	* found in the $config['audioFormats'] array. The first element of these arrays is a boolean indicates
	* whether the conditions are matched. If it is true the second elements of these arrays show
	* where is the matching file in the $list member variable. If the first element is false, the
	* second element can be an index shows from which file can be generated the requested file,
	* or it can be false if there was not so file. For example we need a 128 and a 24 kbps mp3
	* but we have only the 128. The first element will be (true,0) so we have the file at index 0,
	* the second element will be (false,0) so we haven't the 24 kbps file but it can be generated
	* from the 128 kbps file that has the index 0. If we needed a 192 kbps mp3, we got (false,false),
	* so we haven't so file, and we cannot generate it from 128, because 128 is less than 192.
	* @attribute 	array	$reqs
	* @see	{@link $config['audioFormats']}
	*/
	var $reqs = array();

	/**
	* FileList object.
	*
	* @attribute 	object	$list
	* @see	{@link FileList}
	*/
	var $list;

  var $console = false;

	/**
	* Sets up the object
	*
	* @constructor sotf_AudioCheck
	* @param	object	$list	FileList object contains list of files to be checked
	* @use	$config['audioFormats'], $config['bitrateTolerance']
	*/
	function sotf_AudioCheck($list)
	{
		global $config;
		global $config;

		$this->list = & $list;
		for($i=0;$i<count($config['audioFormats']);$i++)			// walk thru the requested formats
		{
			$found = false;								// indicates whether the current audio format has been found
			for($j=0;$j<count($this->list->list);$j++)	// walk thru the the files we have
			{
				// $this->list->list[$j] means the current AudioFile object
				if ($this->list->list[$j]->type != "audio")
					continue;							// This is not an audio, get another one
				if ($config['audioFormats'][$i]['format'] != $this->list->list[$j]->format)
					continue;							// This is not the one we need, get another one
				//if ($config['audioFormats'][$i]['bitrate'] != $this->list->list[$j]->bitrate)
				//	continue;							// This is not the one we need, get another one
				if (abs($this->list->list[$j]->average_bitrate - $config['audioFormats'][$i]['bitrate']) > $config['bitrateTolerance'])
					continue;							// This is not the one we need, get another one
				else
				{
					$this->list->list[$j]->bitrate = $config['audioFormats'][$i]['bitrate'];
					$this->list->list[$j]->average_bitrate = $config['audioFormats'][$i]['bitrate'];
				}
				if ($config['audioFormats'][$i]['channels'] != $this->list->list[$j]->channels)
					continue;							// This is not the one we need, get another one
				if ($config['audioFormats'][$i]['samplerate'] != $this->list->list[$j]->samplerate)
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
				if ($config['audioFormats'][$i]['bitrate'] > $this->list->list[$j]->average_bitrate)
					continue;							// This is not the one we need, get another one
				if ($config['audioFormats'][$i]['channels'] > $this->list->list[$j]->channels)
					continue;							// This is not the one we need, get another one
				if ($config['audioFormats'][$i]['samplerate'] > $this->list->list[$j]->samplerate)
					continue;							// This is not the one we need, get another one
				$found = $j;							// Found a better one
				// easier to encode an mp3 from an mp3
				if (($config['audioFormats'][$i]['format'] != 'mp3') || (($config['audioFormats'][$i]['format'] == 'mp3') && ($this->list->list[$j]->format == 'mp3')))
					break;								// don't need to search for another, leave the loop
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
	* @return	mixed	If the audio file satisfies any requestment returns an integer which is an index of the $config['audioFormats'] global variable. If the file satisfy any requestment returns boolean false
	* @use	$config['audioFormats'], $config['bitrateTolerance']
	*/
	function getRequestIndex($audiofile)
	{
		global $config;
		for($i=0;$i<count($config['audioFormats']);$i++)			// walk thru the requested formats
		{
			if ($audiofile->type != "audio")
				continue;								// This is not an audio, get another one
			if ($config['audioFormats'][$i]['format'] != $audiofile->format)
				continue;								// This is not the one we need, get another one
			//if ($config['audioFormats'][$i]['bitrate'] != $audiofile->bitrate)
			//	continue;								// This is not the one we need, get another one
			if (abs($audiofile->average_bitrate - $config['audioFormats'][$i]['bitrate']) > $config['bitrateTolerance'])
				continue;							// This is not the one we need, get another one
			if ($config['audioFormats'][$i]['channels'] != $audiofile->channels)
				continue;								// This is not the one we need, get another one
			if ($config['audioFormats'][$i]['samplerate'] != $audiofile->samplerate)
				continue;								// This is not the one we need, get another one
			return $j;									// All conditions matched, that's what we need, return the index
		}
		return false;
	} // end func getRequestIndex

	/**
	* Gets best quality audio file
	*
	* @return	mixed	If found return the index of the file, else returns boolean false
	*/
	function getBest()
	{
		$bitrate = 0;								// set minimum bitrate
		$index = false;								// initialize file index
		for($i=0;$i<count($this->list->list);$i++)	// walk thru files
			if ($this->list->list[$i]->type == 'audio')
				if ($this->list->list[$i]->average_bitrate > $bitrate)
				{
					$bitrate = $this->list->list[$i]->average_bitrate;
					$index = $i;
				}
		return $index;
	}

	/**
	* Gets best quality MP3 file
	*
	* @return	mixed	If found return the index of the file, else returns boolean false
	*/
	function getBestMP3()
	{
		$bitrate = 0;								// set minimum bitrate
		$index = false;								// initialize file index
		for($i=0;$i<count($this->list->list);$i++)	// walk thru files
			if ($this->list->list[$i]->type == 'audio')
				if ($this->list->list[$i]->format == 'mp3')
					if ($this->list->list[$i]->average_bitrate > $bitrate)
					{
						$bitrate = $this->list->list[$i]->average_bitrate;
						$index = $i;
					}
		return $index;
	}

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

		return $config['audioFormats'][$index]['bitrate'] . 'kbps_' . $config['audioFormats'][$index]['channels'] . 'chn_' . $config['audioFormats'][$index]['samplerate'] . 'Hz.' . $config['audioFormats'][$index]['format'];
	} // end func getFormatFilename

  ////////////////////////////////////////////////////////
  //
  //  AUDIO CONVERSION
  //
  ///////////////////////////////////////////////////////


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

    function exec($cmd) {
      debug('execute',$cmd);
      exec($cmd);
    }

    function encodeWithLame($cmd)
      {
        global $config, $page;

        if($this->console) {
          echo "<p>" . $page->getlocalized('encoding_mp3') . "<br />\n";
          flush();
          
          $this->progressBar($cmd,$config['lameencRegexp']);
          echo "</p>\n";
          flush();
        } else {
          $this->exec($cmd);
        }
      }
        
    function decodeWithLame($cmd)
      {
        global $config, $page;
        
        if($this->console) {
          echo "<p>" . $page->getlocalized('decoding_mp3') . "<br />\n";
          flush();
          debug('execute',$cmd);
          $result = exec($cmd);
          debug('result',$result);
          for ($i=0;$i<$config['progressBarLength'];$i++)
            echo $config['progressBarChar'];
          echo "</p>\n";
          flush();
        } else {
          $this->exec($cmd);
        }
      }
    
    function encodeWithOgg($cmd)
      {
        global $config, $page;
        
        if($this->console) {
          echo "<p>" . $page->getlocalized('encoding_ogg') . "<br />\n";
          flush();
          $this->progressBar($cmd,$config['oggencRegexp']);
          echo "</p>\n";
          flush();
        } else {
          $this->exec($cmd);
        }
      }
    
    function decodeWithOgg($cmd)
      {
        global $config, $page;
        
        if($this->console) {
          echo "<p>" . $page->getlocalized('decoding_ogg') . "<br />\n";
          flush();
          debug('execute',$cmd);
          exec($cmd);
          for ($i=0;$i<$config['progressBarLength'];$i++)
            echo $config['progressBarChar'];
          echo "</p>\n";
          flush();
        } else {
          $this->exec($cmd);
        }
      }
    
    function convertWithSox($cmd)
      {
        global $config, $page;
        
        if($this->console) {
          echo "<p>" . $page->getlocalized('convert_mono') . "<br />\n";
          flush();
          debug('execute',$cmd);
          exec($cmd);
          for ($i=0;$i<$config['progressBarLength'];$i++)
            echo $config['progressBarChar'];
          echo "</p>\n";
          flush();
        } else {
          $this->exec($cmd);
        }
      }
    
    function checkFile($file) {
      if(!is_readable($file)) {
        raiseError("conversion_failed");
      }
    }

    function rmFile($file) {
      unlink($file) or logError("Could not delete file: $file");
    }

    /** returns array of names of new audio files */
    function convertAll($id) {
      global $config;
      for($i=0; $i<count($config['audioFormats']); $i++) {
        $file = $this->convert($id, $i);
        if($file)
          $files[] = $file;
      }
      return $files;
    }

    /** returns name of new audio file */
    function convert($id, $index) {
      global $config, $page;

      debug('conversion started', $this->getFormatFilename($index));

      if ($this->reqs[$index][0] === true) {
        debug("We have this format already", $index);
        return;
      }

      if ($this->reqs[$index][1] !== false) {
        // We have a better quality audio
        $sourceindex = $this->reqs[$index][1];
      } else {
        // We don't have a better quality audio, get the best
        $sourceindex = $this->getBest();
      }

      if ($sourceindex === false) {
        logError("Could not find a base format to convert format $index");
        return;
      }

      $audioFiles = & $this->list;
      $source = $audioFiles->list[$sourceindex]->getPath();
      debug("source", $source);

      $target = $config['tmpDir'] . '/' . $id . '_' . time() . '_' . $this->getFormatFilename($index);

      $bitrate = $config['audioFormats'][$index]["bitrate"];
      $samplerate = $config['audioFormats'][$index]["samplerate"];
      if ($config['audioFormats'][$index]["channels"] == 1)
        $mode = "mono";
      else
        $mode = "joint";

      if($this->console)
        echo "<p>" . $page->getlocalized('conversion_started') . ' ' . $this->getFormatFilename($index) . "</p>\n";

      if (($config['audioFormats'][$index]['format'] == 'mp3') && ($audioFiles->list[$sourceindex]->format == 'mp3'))
        {
          if (($config['audioFormats'][$index]['channels'] == 2) && ($audioFiles->list[$sourceindex]->channels == 1))
            {
              $tempname1 = $this->getTempWavName();
              $this->decodeWithLame($config['lame'] . " --decode \"$source\" \"$tempname1\"");
              $this->checkFile($tempname1);
              $tempname2 = $this->getTempWavName();
              $this->convertWithSox($config['sox'] . " \"$tempname1\" -c2 \"$tempname2\"");
              $this->checkFile($tempname2);
              $this->rmFile($tempname1);
              $this->encodeWithLame($config['lame'] . " --disptime 1 --cbr -b $bitrate -m $mode --resample $samplerate \"$tempname2\" \"$target\"");
              $this->checkFile($target);
              $this->rmFile($tempname2);
            }
          else
            {
              $this->encodeWithLame($config['lame'] . " --disptime 1 --cbr --mp3input -b $bitrate -m $mode --resample $samplerate \"$source\" \"$target\"");
            }
        }
      elseif (($config['audioFormats'][$index]['format'] == 'ogg') && ($audioFiles->list[$sourceindex]->format == 'mp3'))
        {
          $tempname1 = $this->getTempWavName();
          $this->decodeWithLame($config['lame'] . " --decode \"$source\" \"$tempname1\"");
          $this->checkFile($tempname1);
          if (($config['audioFormats'][$index]['channels'] == 2) && ($audioFiles->list[$sourceindex]->channels == 1))
            {
              $tempname2 = $this->getTempWavName();
              $this->convertWithSox($config['sox'] . " \"$tempname1\" -c2 \"$tempname2\"");
              $this->checkFile($tempname2);
              $this->rmFile($tempname1);
              $this->encodeWithOgg($config['oggenc'] . " -b $bitrate -m $bitrate -M $bitrate --resample $samplerate -o \"$target\" \"$tempname2\"");
              $this->checkFile($target);
              $this->rmFile($tempname2);
            }
          else
            {
              if (($config['audioFormats'][$index]['channels'] == 1) && ($audioFiles->list[$sourceindex]->channels == 2))
                $addparam = "--downmix";
              $this->encodeWithOgg($config['oggenc'] . " -b $bitrate -m $bitrate -M $bitrate --resample $samplerate $addparam -o \"$target\" \"$tempname1\"");
              $this->checkFile($target);
              $this->rmFile($tempname1);
            }
        }
      elseif (($config['audioFormats'][$index]['format'] == 'mp3') && ($audioFiles->list[$sourceindex]->format == 'ogg'))
        {
          $tempname1 = $this->getTempWavName();
          $this->decodeWithOgg($config['oggdec'] . " -o \"$tempname1\" \"$source\"");
          $this->checkFile($tempname1);
          if (($config['audioFormats'][$index]['channels'] == 2) && ($audioFiles->list[$sourceindex]->channels == 1))
            {
              $tempname2 = $this->getTempWavName();
              $this->convertWithSox($config['sox'] . " \"$tempname1\" -c2 \"$tempname2\"");
              $this->checkFile($tempname2);
              $this->rmFile($tempname1);
              $this->encodeWithLame($config['lame'] . " --disptime 1 --cbr -b $bitrate -m $mode --resample $samplerate \"$tempname2\" \"$target\"");
              $this->checkFile($target);
              $this->rmFile($tempname2);
            }
          else
            {
              $this->encodeWithLame($config['lame'] . " --disptime 1 --cbr -b $bitrate -m $mode --resample $samplerate \"$tempname1\" \"$target\"");
              $this->checkFile($target);
              $this->rmFile($tempname1);
            }
        }
      elseif (($config['audioFormats'][$index]['format'] == 'ogg') && ($audioFiles->list[$sourceindex]->format == 'ogg'))
        {
          $tempname1 = $this->getTempWavName();
          $this->decodeWithOgg($config['oggdec'] . " -o \"$tempname1\" \"$source\"");
          $this->checkFile($tempname1);
          if (($config['audioFormats'][$index]['channels'] == 2) && ($audioFiles->list[$sourceindex]->channels == 1))
            {
              $tempname2 = $this->getTempWavName();
              $this->convertWithSox($config['sox'] . " \"$tempname1\" -c2 \"$tempname2\"");
              $this->checkFile($tempname2);
              $this->rmFile($tempname1);
              $this->encodeWithOgg($config['oggenc'] . " -b $bitrate -m $bitrate -M $bitrate --resample $samplerate -o \"$target\" \"$tempname2\"");
              $this->checkFile($target);
              $this->rmFile($tempname2);
            }
          else
            {
              if (($config['audioFormats'][$index]['channels'] == 1) && ($audioFiles->list[$sourceindex]->channels == 2))
                $addparam = "--downmix";
              $this->encodeWithOgg($config['oggenc'] . " -b $bitrate -m $bitrate -M $bitrate --resample $samplerate $addparam -o \"$target\" \"$tempname1\"");
              $this->checkFile($target);
              $this->rmFile($tempname1);
            }
        }
      else
        {
          raiseError("No rule found to convert " . $this->getFormatFilename($index) . "!");
        }

      $this->checkFile($target);
      debug('conversion finished', $target);

      return $target;
    }

} // end class sotf_AudioCheck

?>