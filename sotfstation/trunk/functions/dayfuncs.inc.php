<?
	/**
	 * isLastWeek() - returns TRUE if current week is the last week in month
	 * 
	 * @param $timestamp
	 * @return (bool)
	 */
	function isLastWeek($timestamp){
		if(getDayData($timestamp) == weeksInMonth($timestamp)){
			return true;
		}
		return false;
	}
	
	
	/**
	 * weeksInMonth() - returns the number of week in the given month
	 * 
	 * @param $timestamp
	 * @return 
	 */
	function weeksInMonth($timestamp){
		$days = ceil(date("t",$timestamp) / 7);
						
		$first_day_timestamp = mktime(0,0,1,date("n",$timestamp),1,date("Y",$timestamp));
		$first_day_of_week = date("w",$first_day_timestamp) - 1;
			
		if(($first_day_of_week==5)and(date("t",$timestamp)>30)){
			$days++;
		}else if(($first_day_of_week==-1)and(date("t",$timestamp)>29)){
			$days++;
		}else if(($first_day_of_week!=0)and(date("t",$timestamp)==28)){
			$days++;
		}
		
		return $days;
	}
		
		
	/**
	 * getDayData() - this will return the week number IN THE MONTH
	 * 								of the current timestamp
	 * 
	 * @param $timestamp (timestamp)
	 * @return (int)
	 */
	function getDayData($day_timestamp){
		$daysInMonth = date("t",$day_timestamp);			# total days in month
		$day = date("j",$day_timestamp);							# current day of timestamp				
		$day_of_week = date("w",$day_timestamp);			# day of week...
			
		//parameters definition
		$first_day_timestamp = mktime(0,0,1,date("n",$day_timestamp),1,date("Y",$day_timestamp));
		$first_day_of_week = date("w",$first_day_timestamp) - 1;
			
		//error correction
		if($first_day_of_week==-1){		# first day is a sunday...
			$first_day_of_week = 6;
		}
			
		//more error correction
		if($day_of_week==0){					# first day is a monday
			$day_of_week = 7;
		}
		
		//how many days in first week (default offset)
		$days_in_first_week = 7 - $first_day_of_week;
			
		//what week is this day in?
		if($day <= $days_in_first_week){
			$week = 0;
		}else{
			$week = ceil(($day - $days_in_first_week) / 7);
		}
		
		//increment weeknum, to avoid zero.
		$week++;
		return $week;
	}
	
	/**
	 * odd() - returns TRUE if value is ODD
	 * 
	 * @param $var
	 * @return 
	 */
	function odd($var) {
    return ($var % 2 == 1);
	}

	
	/**
	 * even() - returns TRUE if value is EVEN
	 * 
	 * @param $var
	 * @return 
	 */
	function even($var) {
    return ($var % 2 == 0);
	}

?>