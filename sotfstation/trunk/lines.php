<?
	/******
	* Count the ammount of produced code. Recursive.
	* 
	* Author: Kulikov Alexey - alex@pvl.at , alex@ita-studio.com
	***************/
	
	
	/**
	 * _readDir() - считывает список всех поддирикторий, начиная от
	 * 							заданного пути.
	 * 
	 * @param $path
	 * @return (array)
	 */
	function _readDir($path=""){
		//echo "OPENING PATH :: " . $path . "<br>";
		$dir_tree = array();

		$d = dir($path);
		while($entry = $d->read()){
			if($entry == 'templates_c'){
				continue;
			}
			if(!stristr($entry,'.')){
 		 	 	$dir_tree[$entry] = $entry;
			}
		}
				
		//unset($dir_tree['templates']);
		
		$d->close();

		return $dir_tree;
	}
	
	
	/**
	 * _readPHP() - считывает список всех PHP файлов из дириктории, заданой
	 * 							в параметре $path
	 * 
	 * @param $path
	 * @return (array)
	 */
	function _readPHP($path=""){
		$dir_tree = array();
		$d = dir($path);		
		while(@$entry = $d->read()){
			if(eregi("(.php|.htm)$",$entry)){
  	  	array_push($dir_tree,$entry);
			}
		}
		@$d->close();
		return $dir_tree;
	}
	
	
	/**
	 * _readAll()  - давайте мыслить рекурсивно!
	 * 
	 * @param $path
	 * @return (int)
	 */
	function _readAll($path=""){
		global $size;
		if(is_dir($path)){
			$initial = _readDir($path);
		}
		
		//are there subdirs?
		if(count($initial)>0){
			while(list($key,$val)=each($initial)){
				if(strlen($path)>0){
					$mypath = $path . "/" . $val;
				}else{
					$mypath = $val;
				}
				$overall = $overall + _readAll($mypath);				
				//echo $mypath . "<br>";			
			}
		}
		
		//read files
		if(is_dir($path)){
			$files = _readPHP($path);
		}else{
			$files = array();
		}
		
		while(list($key,$val)=each($files)){
			//open each file and check how many lines of code it has
			if(strlen($path)>0){
				$mypath = $path . "/" . $val;
			}else{
				$mypath = $val;
			}
			$file = fopen($mypath, "r");
			$rf = fread($file, 100000);
			$temp = explode("\n",$rf);
			$tot = count($temp);
			//echo "--" . $val . " : lines " . $tot . "<br>";
			$overall = $overall + $tot;
			$size = $size + filesize($mypath);
			fclose($file);
		}		
		//echo "<b> TOT: </b>" . $overall;		
		return $overall;
	}
		
	
	/**
   * startTiming() - to start the timer for script execution
   * 
   * @return void
	 * 
	 * Version: 1.0  Date: 13.01.2002  Author: Koulikov Alexey 
   */
  function startTiming(){
	  global $startTime;
	  $microtime = microtime();
	  $microsecs = substr($microtime, 2, 8);
	  $secs = substr($microtime, 11);
	  $startTime = "$secs.$microsecs";
  }
	
	
	/**
	 * stopTiming() - to stop the timer for script execution
	 * 
	 * @return end time - float
	 * 
	 * Version: 1.0  Date: 13.01.2002  Author: Koulikov Alexey
	 */
	function stopTiming(){
  	global $startTime;

   	$microtime = microtime();
   	$microsecs = substr($microtime, 2, 8);
   	$secs = substr($microtime, 11);
   	$endTime = "$secs.$microsecs";
    $tottime = round(($endTime - $startTime),4);
		return $tottime;
  }
	
	//Запустим секундомер
  startTiming();
	
	$total = _readAll() - 166;
	switch ($total){
		case ($total < 2500):{$status = 'Baby'; break;}
		case ($total < 5000):{$status = 'Kid'; break;}
		case ($total < 7500):{$status = 'PreTeen'; break;}
		case ($total < 10000):{$status = 'Teenager'; break;}
		case ($total < 20000):{$status = 'PostTeen'; break;}
		case ($total < 30000):{$status = 'Adult'; break;}
		case ($total < 40000):{$status = 'GrandPa'; break;}
		default:{$status = 'Death'; break;}
	}
	
	echo "Overall you (and maybe not you) have written <b>" . $total . "</b> lines of code! <br>
	This code needs " . round($size / 1024,2) . " Kb of space!<br><br>";
	echo "Objectively, <b>your project is a - " . $status . "</b><br><br>";
	echo "This calculation has taken: " . stopTiming() . " seconds!";
?>