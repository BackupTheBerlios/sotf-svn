<?
	## DEPRECATED ################################################################
	#                                                                            #
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
	#                                                                            #
	################################################### END DEPRECATED ###########
		
		
	/**
	 * getDayData() - this will return the occurence number of THIS day in THIS month
	 * 
	 * @param $timestamp (timestamp)
	 * @return (array)
	 */
	function getDayData($day){
		$dayOfWeek = date("w",$day);
		
		$dayOfMonth = date("j",$day);
		$month = date("n",$day);
		$year = date("Y",$day);
		
		//echo "searching for: $dayOfWeek / $dayOfMonth in $month of $year";
		
		#### OCCURCE CALCULATION #########################################################
		#                                                                                #
		//now calculate the first occurence of $dayOfMonth in $month
		$firstDayOfMonth = mktime(1,1,1,$month,1,$year);
		
		for($i=1;$i<31;$i++){
			if(date("w",$firstDayOfMonth) == $dayOfWeek){
				break;
			}
			$firstDayOfMonth = $firstDayOfMonth + 60*60*24;
		}
		
		$firstOccurenceInMonth = $firstDayOfMonth;
		$stayInMonth = date("n",$firstOccurenceInMonth);
		$checkDay = date("j",$firstOccurenceInMonth);
		
		//see if it fits, if no match, increment by a week
		for($i = 1; $i < 6; $i++){
			if($dayOfMonth == $checkDay and $stayInMonth == $month){
				$toReturn['occurence'] = $i;
				break;
			}
			$checkDay = $checkDay + 7;
		}
		
		//check if this is the last occurence in this month
		if(date("n",$firstOccurenceInMonth + $toReturn['occurence']*7*24*60*60) != $month){
			$toReturn['last'] = true;
		}
		#                                                                                #
		############################################### END OCCURENCE CALCULATION ########
		
		#### FULL WEEKNUM CALCULATION ####################################################
		#                                                                                #
		$firstDayOfMonth = mktime(1,1,1,$month,1,$year);
		
		//if the first day is not a monday, then the first week is not a full week
		if(date("w",$firstDayOfMonth) != 1){
		
			$firstMonday = $firstDayOfMonth + 60*60*24;
		
			//now see when the first full week begins
			for($i=1;$i<7;$i++){
				if(date("w",$firstMonday) == 1){
					break;
				}
				$firstMonday = $firstMonday + 60*60*24;
			}
		}else{
			$firstMonday = $firstDayOfMonth;
		}
		
		//now check if day lies in first full week
		for($weeknum = 1; $weeknum <= 4; $weeknum++){
			if($day > $firstMonday and $day < ($firstMonday + 60*60*24*7)){
				$toReturn['fullWeek'] = $weeknum;
				break;
			}elseif($day < $firstMonday){
				$toReturn['fullWeek'] = 0;
				break;
			}else{
				$firstMonday = $firstMonday + 60*60*24*7;
			}
		}
		
		#                                                                                #
		############################################# END FULL WEEKNUM CALCULATION #######
		
		return $toReturn;
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