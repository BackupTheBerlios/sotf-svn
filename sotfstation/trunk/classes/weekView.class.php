<?
	/***
	* SOTFStation: 	Programme Week Overview
	* 
	* Purpose:		Week overview is a challenge, since in HTML tables are built from left
	* 				to right, hence one cannot simply loop though the programm list, but
	* 				develop a separate datatype, that will in turn pre-process the data
	* 				in order to make it loopable from left to right.
	* 
	* @version		0.4
	* 
	* @author		Kulikov Alexey <alex@pvl.at, alex@essentialmind.com>
	********/
	class weekView{
		var $data;
		var $start = array();
		var $end = array();
		
		
		/**
		 * weekView::weekView() - el constructor
		 * 
		 * @return 
		 **/
		function weekView($today){
			$this->setDates($today);
			$this->initData();
		}
		
		
		/**
		 * weekView::setDates()
		 * 
		 * @param $myDate
		 * @return 
		 **/
		function setDates($myDate){
			$myTime = mktime(0,0,0,$myDate[1],$myDate[0],$myDate[2]);
	
			//figure out when this week starts
			$dayOfWeek = date("w",$myTime);
	
			//determine timestamp of the first second of week
			if($dayOfWeek != 1){	//this day is not monday
				$start = $myTime - (($dayOfWeek - 1)*24*60*60);
			}else{
				$start = $myTime;
			}
	
			//determine timestamp of the last second of the week
			if($dayOfWeek != 0){
				$end = mktime(23,59,59,$myDate[1],$myDate[0],$myDate[2]) + ((7 - $dayOfWeek)*24*60*60);
			}else{
				$end = mktime(23,59,59,$myDate[1],$myDate[0],$myDate[2]);
			}
	
			$this->start['string'] = date("Y-m-d H:i:s",$start);
			$this->end['string'] = date("Y-m-d H:i:s",$end);
			$this->start['stamp'] = $start;
			$this->end['stamp'] = $end;
		}
		
		
		/**
		 * weekView::getStart()
		 * 
		 * @return 
		 **/
		function getStart(){
			return $this->start['string'];
		}
		
		
		/**
		 * weekView::getEnd()
		 * 
		 * @return 
		 **/
		function getEnd(){
			return $this->end['string'];
		}
		
		
		/**
		 * weekView::getStartStamp()
		 * 
		 * @return 
		 **/
		function getStartStamp(){
			return $this->start['stamp'];
		}
		
		
		/**
		 * weekView::getEndStamp()
		 * 
		 * @return 
		 **/
		function getEndStamp(){
			return $this->end['stamp'];
		}
		
		
		/**
		 * weekView::initData() - prepare the data array
		 * 
		 * @return 
		 **/
		function initData(){
			//table columns
			$days = array(
							"Monday" 	=> array(),
							"Tuesday"	=> array(),
							"Wednesday"	=> array(),
							"Thursday"	=> array(),
							"Friday"	=> array(),
							"Saturday"	=> array(),
							"Sunday"	=> array()
							);
			
			//table rows				
			for($i=0;$i<24;$i++){
				$this->data[$i] = $days;
			}
		}
		
		
		/**
		 * weekView::add() - add a data value
		 * 
		 * @param $data
		 * @return 
		 **/
		function add($data){
			//based on data timestamp
			
			//correct times
			if(date("i",$data['outtime']) == 0){
				$data['outtime']--;
			}
			
			//I add this data to the correspoding timeslot arrays
			$startHour 	= date("G",$data['intime']);
			$endHour 	= date("G",$data['outtime']);
			$startDay 	= date("l",$data['intime']);
			$endDay		= date("l",$data['outtime']);
			
			
			
			//now see where it has to go
			if($startHour == $endHour and $startDay == $endDay){	//show starts and ends in the same timeslot!
				
				//set the block
				$this->data[$startHour][$startDay][] = $data;
				
			}elseif($startHour < $endHour and $startDay == $endDay){//show start and ends in different slots on the same day
				
				//loop though the hours
				for($i = $startHour; $i <= $endHour; $i++){
					$this->data[$i][$startDay][] = $data;
				}
				
			}elseif($startHour > $endHour and $startDay != $endDay){//midnight show
			
				//loop until midnight
				for($i = $startHour; $i < 24; $i++){
					$this->data[$i][$startDay][] = $data;
				}
				
				//loop after midnigh
				for($i = 0; $i <= $endHour; $i++){
					$this->data[$i][$endDay][] = $data;
				}
				
			}else{
				//somethins is wrong ;)
				trigger_error("Something went wrong! this is not very informative is it?");
			}
		}
		
		
		/**
		 * weekView::out() - show the week overview table
		 * 
		 * @return 
		 **/
		function out(){
			//header
			$out = "<table cellspacing=\"0\" cellpadding=\"0\" width=\"98%\" border=\"0\" align=\"center\">
                    <tbody><tr><td bgcolor=\"#cccccc\">";
					
			$out .= "<table cellspacing=1 cellpadding=4 width=100% border=0>";
			$out .= "<tr><td align=\"center\" nowrap=\"nowrap\" bgcolor=\"#FFFFFF\">&nbsp;</td>
                     <td colspan=\"7\" bgcolor=\"#ffffff\"><div align=\"center\"><a href=\"week.php?date=".date("d-m-Y",$this->getStartStamp() - 24*60*60*7)."\">&lt;&lt;&lt;</a> 
                     ".date("d.m.Y",$this->getStartStamp())." - ".date("d.m.Y",$this->getEndStamp())." <a href=\"week.php?date=".date("d-m-Y",$this->getEndStamp() + 24*60*60)."\">&gt;&gt;&gt;</a></div></td></tr>";
					  
			$out .= "<tr bgcolor=\"#ffffcc\">
					<td nowrap=nowrap bgcolor=#ffffcc align=center>...</td>
					<td width=14% align=center><a href=\"inside.php?date=".date("d-m-Y",$this->getStartStamp())."\">Moday</a></td>
					<td width=14% align=center><a href=\"inside.php?date=".date("d-m-Y",$this->getStartStamp() + 24*60*60)."\">Tuesday</a></td>
					<td width=14% align=center><a href=\"inside.php?date=".date("d-m-Y",$this->getStartStamp() + 24*60*60*2)."\">Wednesday</a></td>
					<td width=14% align=center><a href=\"inside.php?date=".date("d-m-Y",$this->getStartStamp() + 24*60*60*3)."\">Thursday</a></td>
					<td width=14% align=center><a href=\"inside.php?date=".date("d-m-Y",$this->getStartStamp() + 24*60*60*4)."\">Friday</a></td>
					<td width=14% align=center><a href=\"inside.php?date=".date("d-m-Y",$this->getStartStamp() + 24*60*60*5)."\">Saturday</a></td>
					<td width=14% align=center><a href=\"inside.php?date=".date("d-m-Y",$this->getStartStamp() + 24*60*60*6)."\">Sunday</a></td>
					</tr>";
			
			//go through rows
			foreach($this->data as $key => $row){
				//start row
				$out .= "<tr bgcolor=\"#ffffff\">";
				
				//output time
				$out .= "<td nowrap=nowrap bgcolor=#ffffcc align=right><font size=\"-1\" color=\"#999999\"><u>" . $key . ":00</u></font></td>";
				
				//go through days
				foreach($row as $cell){
					//open cell
					$out .= "<td>";
					
					//echo data from each cell
					foreach($cell as $item){
						$out .= "<p><font color=\"#999999\">(" . date("H:i",$item['intime']) . " - " . date("H:i",($item['outtime']+1)) . ")</font><br>";
						$out .= "<a href=\"showprogrammedetails.php?id=".$item['prog_id']."\" onclick=\"NewWindow(this.href,'14','620','500','yes');return false\">" . $item['prog_title'] . "</a>";
						
						//any special needs?
						if($item['special'] != ""){
							if($item['special'] == 'na'){
								$special = "<b><font color=#ff0000>n.a.</font></b>";
							}else{
								$special = "<b>pp</b>";
							}
							$out .= "&nbsp;" . $special;
						}
						
						$out .= "</p>";
					}
					
					//close cell
					$out .= "</td>";
				}
				
				//end row
				$out .= "</tr>";
			}
			
			//footer
			$out .= "</table></td></tr></tbody></table>";
			
			return $out;
		}
	}
?>