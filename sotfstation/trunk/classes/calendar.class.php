<?
	/******
	* Package: Calendar Class Handlers
	* Purpose: To generate easytouser HTML calendars
	* Features: 
	* 					>> 	Full separation of logical entities (month, weeks, days)
	* 							stored in separate objects. This makes customization easy
	* 					>>	Tunable Design
	* 					>>	Highlighting Dates with links (external module calls for data)
	* 					>>	Highlighting currently selected day
	* 
	* Usage Example: Most of the data handling the calendar does itself, and in fact
	* 							 all the subclasses (month, week, day) are PRIVATE classes and
	* 							 should not be called directly!
	* 
	* 							 So, lets build a calendar for the current month:
	* 								
	* 							 <?
	* 									//at first, $_GET['date'] is not set, but we don't care
	* 									//since the calendar will set it to today's date :)
	*                  	$myCal = new calendar($_GET['date']);
	* 
	* 									//mark current day as selected
	*									 	$myCal->select($_GET['date']);
	* 
	* 									//make links to three more days
	*									 	$myCal->setLink(12);
	*									 	$myCal->setLink(13);
	*										$myCal->setLink(14);
	* 
	* 									//output
	*										$myCal->show();
	* 							?>
	* 							
	* 							Other nice ways to add links are
	* 							<?
	* 								$myCal->setLinks("12,16,29");
	* 								
	* 								//or
	* 								$myCal->setLinks("12-8-2002,18-08-2002");
	* 
	* 								//or timestamps
	* 								$myCal->setLinks("12092382,10928292");
	* 
	* 								//or a mixture of all
	* 								$myCal->setLinks("12,16-08-2002,12309292");
	* 							?>
	* 							Be careful with the above methods. Since the timestamps and the dates will
	* 							mark the days according to the whole timescale, where the DAY marker, will
	* 							mark the selected day of the current month ;)
	* 
	* 							The calendar will reconstruct any $_GET[] data that has been in the
	* 							address bar when it was called, so it will not be lost, don't worry :)
	* 
	* 							PS. Please, make sure you specify a correct root in the conf variable below
	* 									these comments. :)
	* 
	* 							PPS. For the calendar to work with the current design, make sure you also 
	* 									 include the CSS at the end of this file inside your project!!!! :)
	************************/
	
	/******
	* Class Day
	* Purpose: to represent a day whithin a week class 
	* Author: Kulikov Alexey - alex@pvl.at, alex@koulikov.cc
	************************/
	class day{
		var $timestamp;
		var $empty = false;
		var $color_off = "#d9d9d9";
		var $color_on = "#000099";
		var $color_link = "#cccccc";
		
		//make sure you include the {color} tag here, such that it gets replaces with the needed color upon request :)
		var $header = "<td bgcolor={color}><font class=date>";
		var $footer = "</font></td>";
		var $selected = false;
		var $link = false;
		
		/**
		 * day::day() - el constructor
		 * 
		 * @param $timestamp (int)
		 * @param $empty (bool)
		 * @return (void)
		 */
		function day($timestamp,$empty=false){
			$this->timestamp = $timestamp;
			$this->empty = $empty;
		}
		
		
		/**
		 * day::setLink()
		 * 
		 * purpose: to mark the current day with a link :)
		 * 
		 * @param $link
		 * @return 
		 */
		function setLink(){
			$this->link = true;
		}
		
		
		/**
		 * day::getDayNumber()
		 * 
		 * @return (int)
		 */
		function getDayNumber(){
			return date("j",$this->timestamp);
		}
		
		
		/**
		 * day::getDayFullNumber()
		 * 
		 * @return (int)
		 */
		function getDayFullNumber(){
			return date("d",$this->timestamp);
		}
		
		
		/**
		 * day::getDayName()
		 * 
		 * @return (string)
		 */
		function getDayName(){
			return date("D",$this->timestamp);
		}
		
		
		/**
		 * day::getDayFullName()
		 * 
		 * @return (string)
		 */
		function getDayFullName(){
			return date("l",$this->timestamp);
		}
		
		
		/**
		 * day::getDayOfWeek()
		 * 
		 * @return (int)
		 */
		function getDayOfWeek(){
			return date("w",$this->timestamp);
		}
		
		
		/**
		 * >>>>> P R I V A T E <<<<<
		 *  day::select()
		 * 
		 * purpose: selected this day
		 * 
		 * @return 
		 */
		function select(){
			$this->selected = true;
		}
		
		
		/**
		 * day::show()
		 * 
		 * @return (string)
		 */
		function show($root){
			//is this day today?
			if($this->selected){
				$this->header = str_replace("{color}",$this->color_on,$this->header);
			}else if(!empty($this->link)){
				$this->header = str_replace("{color}",$this->color_link,$this->header);
			}else{
				$this->header = str_replace("{color}",$this->color_off,$this->header);
			}
			
			$out = $this->header;
			if(!$this->empty){
				
				//form a link without loosing any GET data
				$_GET['date'] = date("j-n-Y", $this->timestamp);
				reset($_GET);
				while(list($key,$val)=each($_GET)){
					if($key=="action"){
						continue;
					}
					$myGet[] = $key . "=" . $val;
				}
				$myGet = "?" . implode("&",$myGet);
					
				$out .= "<a href=\"" . $root . $myGet . "\" class=date>" . $this->getDayFullNumber() . "</a>";

			}else{
				$out .= "&nbsp";
			}
			$out .= $this->footer;
			return $out;
		}
	}
	
	
	/******
	* Class: WEEK
	* Author: Kulikov Alexey - alex@pvl.at, alex@koulikov.cc
	* Prupose: <<!!Private!!>>. To represent one week, this class 
	* 													is part of the MONTH class.
	*************************/	
	class week{
		var $timestamp;
		var $weeknum;
		var $days = array();
		var $header = "<tr align=middle>";
		var $footer = "</tr>";
		
		/**
		 * week::week() - constructor
		 * 
		 * @param $timestamp - timestamp
		 * @param $weeknum - number of the week in the month (private)
		 * @return 
		 */
		function week($timestamp,$weeknum){
			$this->timestamp = $timestamp;
			$this->weeknum = $weeknum;
			
			//first week, must skip the empty days
			$first_day_timestamp = mktime(0,0,1,date("n",$this->timestamp),1,date("Y",$this->timestamp));
			$first_day_of_week = date("w",$first_day_timestamp) - 1;
			if($first_day_of_week==-1){
				$first_day_of_week = 6;
			}
			
			//now fill in the week with days
			for($x=1;$x<=7;$x++){
				//what is the current day number?
				$day_num = $x+(7*$weeknum) - $first_day_of_week;
				
				//when does this day start?
				$new_timestamp = mktime(0,0,1,date("n",$this->timestamp),$day_num,date("Y",$this->timestamp));
				
				//if day is invalid, it belongs to another month :)
				if(($day_num < 1) or ($day_num > date("t",$this->timestamp))){
					$my_flag = true;
				}else{
					$my_flag = false;
				}
				
				//create day
				$new_day = new day($new_timestamp,$my_flag);
				array_push($this->days,$new_day);
			}
		}
		
		
		/**
		 * week::getWeekNumber() - returns the number of the week in the year
		 * 
		 * @return (int)
		 */
		function getWeekNumber(){
			return date("W",$this->timestamp);
		}
			
		
		/**
		 * >>>> P R I V A T E <<<<<
		 * week::select()
		 * 
		 * purpose: selects a day in the week
		 * 
		 * @param $day
		 * @return 
		 */
		function select($day){
			$this->days[$day]->select();
		}
		
		
		/**
		 * >>>> P R I V A T E <<<<<
		 * week::select()
		 * 
		 * purpose: selects a day in the week
		 * 
		 * @param $day
		 * @return 
		 */
		function setLink($day){
			$this->days[$day]->setLink();
		}
		
		
		/**
		 * week::show() - outputs the current week to the screen
		 * 
		 * @return (string)
		 */
		function show($root=""){
			$out = $this->header;
			reset($this->days);
			while(list($key,$val)=each($this->days)){
				$out .= $val->show($root);
			}
			$out .= $this->footer;
			return $out;
		}
	}
	
	
	/******
	* Class: Month
	* Author: Kulikov Alexey - alex@pvl.at, alex@koulikov.cc
	* Purpose: <<!!Private!!>> To represent a calendar month
	***********************/
	class month{
		var $timestamp;
		var $weeks = array();
		
		//link tags a replresented by {prev} and {next}.
		var $header = "<table cellspacing=3 cellpadding=2 border=0 width=210><tbody>
											<tr align=middle bgcolor=#cccccc>
												<td><font class=date>&nbsp;<a href=\"{prev}\" class=date>&lt;</a></font></td>
												<td colspan=3><font class=datee>{name}</font></td>
    							 			<td colspan=2><font class=datee>{year}</font></td>
												<td><font class=date><a href=\"{next}\" class=date>&gt;</a></font></td>
											</tr>
  								 
									 <tr align=middle bgcolor=#cccccc>
									 		<td align=center width=30><font class=datee>mo</font></td>
    							 		<td align=center width=30><font class=datee>di</font></td>
											<td align=center width=30><font class=datee>mi</font></td>
											<td align=center width=30><font class=datee>do</font></td>
    							 		<td align=center width=30><font class=datee>fr</font></td>
    							 		<td align=center width=30><font class=datee>sa</font></td>
											<td align=center width=30><font class=datee>so</font></td></tr>";
											
		var $footer = "</tbody></table>";
		
		/**
		 * month::month() - constructor :)
		 * 
		 * @param $timestamp
		 * @return 
		 */
		function month($timestamp){
			$this->timestamp = $timestamp;
			$days = ceil($this->getDaysInMonth() / 7);
						
			
			$first_day_timestamp = mktime(0,0,1,date("n",$this->timestamp),1,date("Y",$this->timestamp));
			$first_day_of_week = date("w",$first_day_timestamp) - 1;
			
			if(($first_day_of_week==5)and($this->getDaysInMonth()>30)){
				$days++;
			}else if(($first_day_of_week==-1)and($this->getDaysInMonth()>29)){
				$days++;
			}else if(($first_day_of_week!=0)and($this->getDaysInMonth()==28)){
				$days++;
			}

			for($x=0;$x<$days;$x++){
				$new_week = new week($this->timestamp,$x);
				array_push($this->weeks,$new_week);
			}
		}
		
		
		/**
		 * month::getMonthName()
		 * 
		 * purpose: to retrun the name of the current month, three letters
		 * 
		 * @return (string)
		 */
		function getMonthName(){
			return date("M",$this->timestamp);
		}
		
		
		/**
		 * month::getMonthFullName()
		 * 
		 * purpose: to return the name of the current month, full string
		 * 
		 * @return (string)
		 */
		function getMonthFullName(){
			return date("F",$this->timestamp);
		}
		
		
		/**
		 * month::getMonthNumber()
		 * 
		 * purpose: to return the number of the current month, no leading zeroes
		 * 
		 * @return (int)
		 */
		function getMonthNumber(){
			return date("n",$this->timestamp);
		}
		
		
		/**
		 * month::getMonthFullNumber()
		 * 
		 * purpose: to return the number of the current month, with leading zeroes
		 * 
		 * @return (int)
		 */
		function getMonthFullNumber(){
			return date("m",$this->timestamp);
		}
		
		
		/**
		 * month::getDaysInMonth()
		 * 
		 * purpose: to return the number of days in the current month
		 * 
		 * @return (int)
		 */
		function getDaysInMonth(){
			return date("t",$this->timestamp);
		}
		
		
		/**
		 * month::getWeeksNum()
		 * 
		 * purpose: to return the number of weeks in the current month
		 * 
		 * @return (int)
		 */
		function getWeeksNum(){
			return count($this->weeks);
		}
		
		
		/**
		 * month::getWeek()
		 * 
		 * purpose: to return a week object
		 * 
		 * @param $week_num
		 * @return (object)
		 */
		function getWeek($week_num){
			return $this->weeks['week_num'];
		}
		
		
		/**
		 * month::getWeeks()
		 * 
		 * purpose: to return an array of week objects
		 * 
		 * @return (array::object)
		 */
		function getWeeks(){
			return $this->weeks;
		}
		
		
		/**
		 * >>>> P R I V A T E <<<< 
		 * month::select()
		 * 
		 * purpose: selects a calendar day
		 * 
		 * @param $day
		 * @return 
		 */
		function select($day=""){
			$myDay = $this->getDayData($day);
			
			//do the selection
			$this->weeks[$myDay[week]]->select($myDay[day] - 1);
		}
		
		
		/**
		 * >>>> P R I V A T E <<<< 
		 * month::select()
		 * 
		 * purpose: makes a calendar day linked
		 * 
		 * @param $day
		 * @return 
		 */
		function setLink($day=""){
			$myDay = $this->getDayData($day);
			
			//do the selection
			$this->weeks[$myDay[week]]->setLink($myDay[day] - 1);
		}
		
		
		/**
		 * >>>> P R I V A T E <<<<< 
		 * month::detDayData()
		 * 
		 * purpose: figure out what day of month and week it is :)
		 * 
		 * @param $day
		 * @return 
		 */
		function getDayData($day=""){
			if((empty($day)) or ($day < 1) or ($day > $this->getDaysInMonth())){
				$day = date("j");
			}
			$day_timestamp = mktime(12,0,0,$this->getMonthNumber(),$day,date("Y",$this->timestamp));
			$day_of_week = date("w",$day_timestamp);
			
			//parameters definition
			$first_day_timestamp = mktime(0,0,1,$this->getMonthNumber(),1,date("Y",$this->timestamp));
			$first_day_of_week = date("w",$first_day_timestamp) - 1;
			
			//error correction
			if($first_day_of_week==-1){
				$first_day_of_week = 6;
			}
			
			//more error correction
			if($day_of_week==0){
				$day_of_week = 7;
			}
			$days_in_first_week = 7 - $first_day_of_week;
			
			//what week is this day in?
			if($day <= $days_in_first_week){
				$week = 0;
			}else{
				$week = ceil(($day - $days_in_first_week) / 7);
			}
			
			$data['week'] = $week;
			$data['day'] = $day_of_week;
			return $data;
		}
		
		
		/**
		 * month::show()
		 * 
		 * purpose: simple output
		 * 
		 * @return 
		 */
		function show($root){
			$this->header = str_replace("{name}",$this->getMonthFullName(),$this->header);
			$this->header = str_replace("{year}",date("Y",$this->timestamp),$this->header);
			
			//work around the prev/next links :)
			$_GET['date'] = date("j-n-Y", mktime(0,0,0,$this->getMonthNumber() - 1,1,date("Y",$this->timestamp)));
			reset($_GET);
			while(list($key,$val)=each($_GET)){
				if($key=="action"){
					continue;
				}
				$myGet[] = $key . "=" . $val;
			}
			$myGet = "?" . implode("&",$myGet);
			
			$this->header = str_replace("{prev}",$root . $myGet,$this->header);
			
			$myGet = array();
			$_GET['date'] = date("j-n-Y", mktime(0,0,0,$this->getMonthNumber() + 1,1,date("Y",$this->timestamp)));
			reset($_GET);
			while(list($key,$val)=each($_GET)){
				$myGet[] = $key . "=" . $val;
			}
			$myGet = "?" . implode("&",$myGet);
			$this->header = str_replace("{next}",$root . $myGet,$this->header);
			
			
			$out = $this->header;
			reset($this->weeks);
			while(list($key,$val)=each($this->weeks)){
				$out .= $val->show($root);
			}
			$out .= $this->footer;
			return $out;
		}
	}
	
	
	/*****
	* Class Calendar (MAIN CLASS)
	* Author: Kulikov Alexey - alex@pvl.at, alex@koulikov.cc
	* Purpose: To build a calendar from scratch!
	********************/
	class calendar{
		var $timestamp;
		var $month;
		
		
		/**
		 * calendar::calendar()
		 * 
		 * purpose: el constructor
		 * 
		 * @param $timestamp (int)
		 * @return (void)
		 */
		function calendar($timestamp=""){
			if(empty($timestamp)){
				$timestamp = time();
			}else if(strpos($timestamp,"-")>0){
				$myDate = explode("-",$timestamp);
				$timestamp = mktime(0,0,0,$myDate[1],$myDate[0],$myDate[2]);
				
				if($timestamp==-1){
					$timestamp = time();
				}
			}
			
			if(!is_int($timestamp)){
				$timestamp = time();
			}
			$this->timestamp = $timestamp;
			$this->month = new month($timestamp);
		}
						
		
		/**
		 * calendar::show()
		 * 
		 * purpose: to output the current month view
		 * 
		 * @return (echo)
		 */
		function show($root=""){
			if(empty($root)){
				$root = "calendar.class.php";
			}
			return $this->month->show($root);
		}
				
		
		/**
		 * calendar::select() - selects a day on the calendar
		 * 
		 * purpose: to select a day on the calendar. This function will take as parameter either
		 * 					1. Nothing, then today will be selected
		 * 					2. A valid timestamp of some day > Please be careful with this
		 * 					3. The number of the day you wish to select (int) in this month
		 * 
		 * @param $timestamp
		 * @return (void)
		 */
		function select($timestamp=""){
			if(empty($timestamp)){
				$timestamp = time();
			}else if(strlen($timestamp)<3){
				$timestamp = mktime(0,0,0,date("n",$this->timestamp),$timestamp,date("Y",$this->timestamp));
			}else if(strpos($timestamp,"-")>0){
				$myDate = explode("-",$timestamp);
				$timestamp = mktime(0,0,0,$myDate[1],$myDate[0],$myDate[2]);
				
				if($timestamp==-1){
					$timestamp = time();
				}
			}
			
			//does the timestamp lie inside THIS month?
			$start = mktime(0,0,0,date("n",$this->timestamp),1,date("Y",$this->timestamp));
			$end = mktime(0,0,0,date("n",$this->timestamp),$this->month->getDaysInMonth(),date("Y",$this->timestamp));
			
			if(($timestamp >= $start) and ($timestamp <= $end)){
				$this->month->select(date("j",$timestamp));
			}
		}
		
		
		/**
		 * calendar::setLink()
		 * 
		 * purpose: to mark a day in the calendar with a link. This function will take as parameter either
		 * 					1. Nothing, then today will be selected
		 * 					2. A valid timestamp of some day > Please be careful with this
		 * 					3. The number of the day you wish to select (int) in this month
		 * 					4. A date in the form DD-MM-YYYY
		 * 
		 * @param $timestamp
		 * @return 
		 */
		function setLink($timestamp=""){
			if(empty($timestamp)){
				$timestamp = time();
			}else if(strlen($timestamp)<3){
				$timestamp = mktime(0,0,0,date("n",$this->timestamp),$timestamp,date("Y",$this->timestamp));
			}else if(strpos($timestamp,"-")>0){
				$myDate = explode("-",$timestamp);
				$timestamp = mktime(0,0,0,$myDate[1],$myDate[0],$myDate[2]);
				
				if($timestamp==-1){
					$timestamp = time();
				}
			}
			
			//does the timestamp lie inside THIS month?
			$start = mktime(0,0,0,date("n",$this->timestamp),1,date("Y",$this->timestamp));
			$end = mktime(23,59,59,date("n",$this->timestamp),$this->month->getDaysInMonth(),date("Y",$this->timestamp));
			
			if(($timestamp >= $start) and ($timestamp <= $end)){
				$this->month->setLink(date("j",$timestamp));
			}
		}
		
		
		/**
		 * calendar::setLinks()
		 * 
		 * purpose: mark many days with links. Will accept either:
		 * 					1. an Array of integers / dates / timestamps
		 * 					2. an Integer / date / timestamp
		 * 					3. A comma separated list of integers / dates / timestamps.
		 * 
		 * @param $links
		 * @return 
		 */
		function setLinks($links=""){
			//empty case
			if(empty($links)){
				$this->setLink();
				
			//array case
			}else if(is_array($links)){
				reset($links);
				while(list($key,$val)=each($links)){
					$this->setLink($val);
				}
				
			//comma separated list case
			}else if(strpos($links,",")>0){
				$links = str_replace(" ","",$links);
				$links = explode(",",$links);
				reset($links);
				while(list($key,$val)=each($links)){
					$this->setLink($val);
				}
			
			//since entity case
			}else if(is_int($links)){
				$this->setLink($links);
			
			//all other cases
			}else{
				$this->setLink();
			}
		}
		
		
		/**
		 * calendar::debug()
		 * 
		 * purpose: to see the contents of the calendar class
		 * 
		 * @return (echo)
		 */
		function debug(){
			echo "<pre>";
			print_r($this->month);
			echo "</pre>";
		}
	}
?>