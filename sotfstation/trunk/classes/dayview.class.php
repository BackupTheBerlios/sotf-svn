<?
	/***
	* Class DayBlock :: is a subclass of class dayView
	* Author: Kulikov Alexey - alex@ita-studio.com, alex@pvl.at
	* Purpose: To represent a block of time whithin a calendar day
	***********/
	class dayBlock{
		var $start_time;
		var $end_time;
		var $id;
		var $description;
		var $owner;
		var $owner_name;
		var $special;
		
		/**
		 * dayBlock::dayBlock()
		 * 
		 * purpose: el constructor
		 * 
		 * @param $id (int)
		 * @param $start_time (timestamp)
		 * @param $end_time (timestamp)
		 * @param $description (string)
		 * @param $owner (int)
		 * @param $owner_name (string)
		 * @return (void)
		 */
		function dayBlock($id,$start_time,$end_time,$description,$owner,$owner_name){
			$this->id = $id;
			$this->start_time = $start_time;
			$this->end_time = $end_time;
			$this->description = $description;
			$this->owner = $owner;
			$this->owner_name = $owner_name;
		}
		
		/**
		 * dayBlock::getID()
		 * 
		 * purpose: return object ID
		 * 
		 * @return (int) 
		 */
		function getID(){
			return $this->id;
		}
		
		/**
		 * dayBlock::getStart()
		 * 
		 * purpose: return starttime timestamp
		 * 
		 * @return (timestamp) 
		 */
		function getStart(){
			return $this->start_time;
		}
		
		/**
		 * dayBlock::getEnd()
		 * 
		 * purpose: return endtime timestamp
		 * 
		 * @return (timestamp)
		 */
		function getEnd(){
			return $this->end_time;
		}
		
		/**
		 * dayBlock::getText()
		 * 
		 * purpose: return the block description
		 * 
		 * @return (string) 
		 */
		function getText(){
			return $this->description;
		}
		
		function getOwner(){
			return $this->owner;
		}
		
		function getOwnerName(){
			return $this->owner_name;
		}
		
		function setSpecial($special){
			$this->special = $special;
		}
		
		function getSpecial(){
			return $this->special;
		}
	}
	
	/***
	* Class DayView
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	* Purpose: To represent a calendar day
	****************/
	class dayView{
		var $header = "<table cellspacing=0 cellpadding=0 width=\"98%\" border=0 align=\"center\">
                    <tbody><tr><td bgcolor={color_border}> <table cellspacing=1 cellpadding=4 width=\"100%\" border=0>
                    <tbody><tr bgcolor={color_off}><td noWrap bgcolor={color_off} align=center>...</td>
                    <td width=\"100%\" bgcolor={color_on}><img height=16 src=\"{class_root}templates/img/i-remindergray.gif\" width=24 border=0></td></tr>";
										
		var $footer = "<tr><td noWrap bgcolor={color_off} align=center>...</td><td width=\"100%\" bgcolor={color_on}>
									 <img height=16 src=\"{class_root}templates/img/i-remindergray.gif\" width=24 border=0></td></tr></tbody>
                   </table></td></tr>
									 <tr> 
          <td><div align=\"right\"><img src=\"{class_root}templates/img/shadow1.gif\" height=\"9\"></div></td>
        </tr>
									 </tbody></table>";
									 
		var $color_on = "#ffffff";
		var $color_off = "#ffffcc";
		var $color_border = "#cccccc";
		var $color_text = "#999999";
		var $row = "<tr><td noWrap bgcolor={color_off} align=right><font size=-1 color={color_text}><u>{time_fragment}:00</u></font></td><td width=\"100%\" bgcolor={color_on}>{content}</td>
										</tr>";
								
		var $no_content = "<img height=16 src=\"{class_root}templates/img/i-remindergray.gif\" width=24 border=0>";
		var $content = "<table cellspacing=0 cellpadding=0 border=0>
                <tbody><tr><td width=24>{bell}</td>
                <td bgcolor={color_on}>{content_owner}<font size=-1><a href=\"showprogrammedetails.php?id={content_id}\" onclick=\"NewWindow(this.href,'14','620','400','yes');return false\">{content_name}</a></font>&nbsp;{special}&nbsp;<font size=-1 color={color_text}>({content_start_time}-{content_end_time})</font></td></tr></tbody></table>";
		
		var $content_owner = "<font color={color_text}>(<a href=\"showuserdata.php?user={owner_id}\" class=date2 onclick=\"NewWindow(this.href,'14','620','400','yes');return false\">{owner_name}</a>)</font> ";
		
		var $bell_on = "<img height=16 src=\"{class_root}templates/img/i-reminder.gif\" width=24 border=0>";
				
		var $timestamp;
		var $blocks = array();
		var $cal_owner;
		var $cal_browser;
		
		/**
		 * dayView::dayView()
		 * 
		 * purpose: El Constructor
		 * 
		 * @param $timestamp
		 * @return 
		 */
		function dayView($calendar_browser="",$calendar_owner="",$timestamp=""){
			//empty input
			if(empty($timestamp)){
				$timestamp = time();
			
			//timestamp is a date
			}else if(strpos($timestamp,"-")>0){
				$myDate = explode("-",$timestamp);
				$timestamp = mktime(0,0,0,$myDate[1],$myDate[0],$myDate[2]);
				
				if($timestamp==-1){
					$timestamp = time();
				}
			}
			//all other cases, timestamp is presumed to be a timestamp
			
			//error check
			if(!is_int($timestamp)){
				$timestamp = time();
			}
			
			$this->cal_browser = $calendar_browser;
			$this->timestamp = $timestamp;
			$this->cal_owner = $calendar_owner;
		}
		
		
		/**
		 * dayView::addBlock()
		 * 
		 * purpose: add a block of time to the calendar day
		 * 
		 * @param $id (int)
		 * @param $start_time (time)
		 * @param $end_time	(time)
		 * @param $desc	(string)
		 * @param $owner (int)
		 * @param $owner_name (string)
		 * @param $special (string)
		 * @return (void)
		 */
		function addBlock($id,$start_time,$end_time,$desc,$owner=0,$owner_name="nobody",$special=false){
			if(strpos($start_time,":")>0){
				$myTimes = explode(":",$start_time);
				$start_time = mktime($myTimes[0],$myTimes[1],0,date("m",$this->timestamp),date("d",$this->timestamp),date("Y",$this->timestamp));
			}
			
			if(strpos($end_time,":")>0){
				$myTimes = explode(":",$end_time);
				$end_time = mktime($myTimes[0],$myTimes[1],0,date("m",$this->timestamp),date("d",$this->timestamp),date("Y",$this->timestamp));
			}
			
			$new_block = new dayBlock($id,$start_time,$end_time,$desc,$owner,$owner_name);
			
			if($special){
				$new_block->setSpecial($special);
			}
			
			$this->blocks[$id] = $new_block;
		}
		
		/**
		 * dayView::show()
		 * 
		 * purpose: actual HTML generation
		 * 
		 * @return (string)
		 */
		function show($owner=0, $access_level=0, $root="",$checked=""){
			//lets rock
			$out = $this->header;			# append header to overall HTML template
			for($x=0;$x<=23;$x++){		# loop though all the hours of the calendar
				//define this hour
				$myBlockStart = mktime($x,0,0,date("m",$this->timestamp),date("d",$this->timestamp),date("Y",$this->timestamp));
				$myBlockEnd = mktime($x+1,0,0,date("m",$this->timestamp),date("d",$this->timestamp),date("Y",$this->timestamp));
				
				//are there timeblocks whithin this hour?
				if(count($this->blocks)>0){
					$this_row_content = "";
					$content_flag = false;
					
					reset($this->blocks);
					while(list($key,$val)=each($this->blocks)){			# loop though all the blocks that lie whithin THIS hour
						
						if((($val->getStart() >= $myBlockStart) and ($val->getStart() < $myBlockEnd)) or 
							 (($val->getEnd() > $myBlockStart) and ($val->getEnd() <= $myBlockEnd)) or
							 (($val->getEnd() > $myBlockEnd) and ($val->getStart() < $myBlockStart))){
						
							//$myRow = str_replace("{content}",$this->content,$this->row);
							$this_row_content .= $this->content;
							
							//parse content
							if(date("i",$val->getEnd())==59){
								$myout = date("H:i",$val->getEnd()+1);
							}else{
								$myout = date("H:i",$val->getEnd());
							}
							
							//fill content of THIS time block
							$this_row_content = str_replace("{content_name}",$val->getText(),$this_row_content);
							$this_row_content = str_replace("{content_start_time}",date("H:i",$val->getStart()),$this_row_content);
							$this_row_content = str_replace("{content_end_time}",$myout,$this_row_content);
							$this_row_content = str_replace("{content_id}",$val->getID(),$this_row_content);
							
							//process owner data	(all that comes with $owner and $ownername)
							$ownerName = $val->getOwnerName();
							if(!empty($ownerName)){
								$myOwner = str_replace("{owner_id}",$val->getOwner(),$this->content_owner);
								$myOwner = str_replace("{owner_name}",$val->getOwnerName(),$myOwner);
								$this_row_content= str_replace("{content_owner}",$myOwner,$this_row_content);
							}else{
								$this_row_content = str_replace("{content_owner}","",$this_row_content);
							}
							
							//process special data (all that comes in the $special flag)
							if(!$val->getSpecial()){
								$this_row_content = str_replace("{special}",'',$this_row_content);
							}else{
								if($val->getSpecial()=='pp'){
									$spec = "<b>pp</b>";
								}else if($val->getSpecial()=='na'){
									$spec = "<b><font color=#ff0000>n.a.</font></b>";
								}else{
									$spec = "";
								}
								$this_row_content = str_replace("{special}",$spec,$this_row_content);
							}
							
							//show only one bell per hour
							if(!$content_flag){
								$this_row_content = str_replace("{bell}",$this->bell_on,$this_row_content);
							}else{
								$this_row_content = str_replace("{bell}","",$this_row_content);
							}
					
							$content_flag = true;
						}else if(!$content_flag){
							$myRow = str_replace("{content}",$this->no_content,$this->row);
						}
					}
					
					if($content_flag){
						$myRow = str_replace("{content}",$this_row_content,$this->row);
						//$myRow = str_replace("{disabled_flag}","DISABLED",$myRow);		# this is form related
					}
					
				}else{	# there is no content in this hour - empty
					$myRow = str_replace("{content}",$this->no_content,$this->row);
				}
				
				//further code is related to calendars with form support, it has been specifically
				//cut out from this class, since it is not used in the Station Management console!
				/*
				//this is for securitely locked calendars only, can ignore in most cases
				if(!empty($this->cal_owner)){
					if($this->cal_browser!=$this->cal_owner){
						if($access_level<10){
							$myRow = str_replace("{disabled_flag}","DISABLED",$myRow);
						}
					}
				}
				//end piece of code to ignore in most cases ;)
				
				//this is related to form checkbox only, it has been cutout from this version
				//of the class, please ignore
				if(isset($checked[$x])){
					$myRow = str_replace("{checked_flag}","checked",$myRow);
				}else{
					$myRow = str_replace("{checked_flag}","",$myRow);
				}
				//end this ignore bit
				*/
				
				//some basic output ;)
				$myRow = str_replace("{count}",$x,$myRow);
				$myRow = str_replace("{time_fragment}",$x,$myRow);
				$out .= $myRow;
			}
			
			//add on the footer to the overall HTML block
			$out .= $this->footer;
			
			//parse through
			$out = str_replace("{color_off}",$this->color_off,$out);
			$out = str_replace("{color_on}",$this->color_on,$out);
			$out = str_replace("{color_border}",$this->color_border,$out);
			$out = str_replace("{color_text}",$this->color_text,$out);
			$out = str_replace("{class_root}",$root,$out);
			return $out;
		}
		
		
		function debug(){
			echo "<b>Header:</b> " . htmlspecialchars($this->header) . "<br>";
			echo "<b>Footer:</b> " . htmlspecialchars($this->footer) . "<br>";
			echo "<b>On Color:</b> " . $this->color_on . "<br>";
			echo "<b>Off Color:</b> " . $this->color_off . "<br>";
			echo "<b>Text Color:</b> " . $this->color_text . "<br>";
			echo "<b>Border Color:</b> " . $this->color_border . "<br><br>";
			echo "<b>Row Template:</b> " . htmlspecialchars($this->row) . "<br>";
			echo "<b>Full Row:</b> " . htmlspecialchars($this->content) . "<br>";
			echo "<b>Empty Row:</b> " . htmlspecialchars($this->no_content) . "<br><br>";
			echo "<b>Timestamp:</b> " . $this->timestamp . " :: " . date("d/m/Y H:i:s",$this->timestamp) . "<br>";
			echo "<b>Blocks:</b> ";
			echo "<pre>";
			print_r($this->blocks);
			echo "</pre>";
		}
	}
?>