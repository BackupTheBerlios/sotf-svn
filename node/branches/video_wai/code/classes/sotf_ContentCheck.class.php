<?php


/**

* DESCRIPTION MISSING
*
* @author	Martin Schmidt <ptmschmidt@fh-stpoelten.ac.at>
* @package	StreamOnTheFly
* @version	0.1
*/

class sotf_ContentCheck

{

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
	* @constructor sotf_ContentCheck
	* @param	object	$list	FileList object contains list of files to be checked
	* @use	$config['audioFormats'], $config['bitrateTolerance']
	*/

	function sotf_ContentCheck($list)
	{
		global $config;
		$this->list = & $list;

			
	} // end func sotf_AudioCheck


	function selectType(){
	
		for($j=0;$j<count($this->list->list);$j++)	// walk thru the the files we have
			{
						
				if ($this->list->list[$j]->type == "video"){
					$checker = & new sotf_VideoCheck($this->list);
					return $checker;
				}

				else if($this->list->list[$j]->type == "audio"){
					$checker = & new sotf_AudioCheck($this->list);
					return $checker;
				}
			}
	} // end func selectType

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
		for($i=0;$i<count($config[$this->prefix.'Formats']);$i++)			// walk thru the requested formats
		{

			if ($audiofile->type != "audio")
				continue;								// This is not an audio, get another one

			if ($config[$this->prefix.'Formats'][$i]['format'] != $audiofile->format)
				continue;								// This is not the one we need, get another one

			//if ($config['audioFormats'][$i]['bitrate'] != $audiofile->bitrate)

			//	continue;								// This is not the one we need, get another one
			if (abs($audiofile->average_bitrate - $config[$this->prefix.'Formats'][$i]['bitrate']) > $config['bitrateTolerance'])
				continue;							// This is not the one we need, get another one
			if ($config[$this->prefix.'Formats'][$i]['channels'] != $audiofile->channels)
				continue;								// This is not the one we need, get another one
			if ($config[$this->prefix.'Formats'][$i]['samplerate'] != $audiofile->samplerate)
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
			if ($this->list->list[$i]->type == $this->prefix)
				if ($this->list->list[$i]->average_bitrate > $bitrate)
				{
					$bitrate = $this->list->list[$i]->average_bitrate;
					$index = $i;
				}
		return $index;
	}


	


  ////////////////////////////////////////////////////////
  //
  //  AUDIO CONVERSION
  //
  ///////////////////////////////////////////////////////




    function progressBar($cmd,$regexp, $totalframes=0, $returndata=false, $errorRegexp="")

      {

        global $config;


        $line = "";

        $out = 0;

        $left = $config['progressBarLength'];
		
		$output=array();


        debug('execute',$cmd);
        $fp = popen($cmd . ' 2>&1', 'r');
		//echo $cmd."<br>&nbsp;<br>";
		$output['data']="$cmd\n";
		$output['error']="";
        while(!feof($fp))

          {

            $data = fread($fp,1);
			$output ['data'].= $data;
			//echo $data;
            if ((ord($data) == 13) || (ord($data) == 10))

              {
				
                if (preg_match($regexp,$line,$match))

                  {
				  	if ($totalframes){
					
						//echo "<br/>t:$totalframes|";
						 $curr = (integer) ((((integer) $match[1])/$totalframes*100) * $config['progressBarLength'] / 100);
						 
						//echo "c:$curr<br/>";
					}
                    else $curr = (integer) (((integer) $match[1]) * $config['progressBarLength'] / 100);
                    
					if($curr<=$config['progressBarLength']){
						for ($i=$out;$i<$curr;$i++)
						  {
							echo $config['progressBarChar'];
							$out++;
							$left--;
						  }
					}
                  }
				  
				 /*if ($errorRegexp && preg_match($errorRegexp,$line,$match)){
				 	$output['error'].= ucfirst($match[1])."<br>";
				 }*/
                
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
		if($returndata) return $output;

      }



    function exec($cmd) {

      debug('execute',$cmd);

      exec($cmd);

    }




    function rmFile($file) {

      unlink($file) or logError("Could not delete file: $file");

    }

	 function getTempWavName()

      {

        global $config;

        $tempname = tempnam($config['tmpDir'],"__");

        unlink($tempname);

        return $tempname. ".wav";

      }


    /** returns array of names of new audio files */

    function convertAll($id) {

      global $config;

      for($i=0; $i<count($config[$this->prefix.'Formats']); $i++) {
		
        $file = $this->convert($id, $i);
        if($file)
			
          $files[] = $file;

      }
		
      return $files;

    }

} // end class sotf_AudioCheck



?>