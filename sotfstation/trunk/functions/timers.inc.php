<?
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
	
	
	/**
	 * clean()
	 * 
	 * @param $array
	 * @return 
	 */
	function clean($array){
		reset($array);
		while(list($key,$val)=each($array)){
			$array[$key] = trim(htmlspecialchars($val));
		}
		return $array;
	}
?>