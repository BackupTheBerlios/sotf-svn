<?

	//default error corrector...
	if(!isset($_GET['block'])){
		$_GET['block'] = 1;
		$db_block = 0;
	}else{
		$db_block = $_GET['block'] - 1;
	}
			
	/******************
	* Class: Page Splitter
	* Author: Koulikov Alexey
	* Purpose: To create a sliding navigation bar when browsing multiple
	* 				 page result sets.
	* Date: 03.05.2002
	* Version: 2.0
	***********/
	class pageSplit{
    var $max_elements;
    var $block_size;
    var $pointer;
		var $link;
		var $slider = 7;
		
		//design variables...
		var $header;
		var $footer;
		var $body = "<a href={link}>{text}</a>";
		var $body_current = "<b>{text}</b>";
		var $separator = ", ";
		var $prev = "Prev";
		var $next = "Next";
		
    /**
     * pageSplit::pageSplit() - el constructor
     * 
     * @param $pointer
     * @param $max_elements
     * @param $toshow
     * @return 
     */
    function pageSplit($pointer,$max_elements,$link='',$toshow=25){			
			//set variables
			$this->max_elements = $max_elements;
			$this->block_size = $toshow;
			$this->pointer = $pointer;
			$this->link = $link;
		}          
      
		/**
		 * pageSplit::rebuild() - is used to rebuild the link that was used
		 * 												in the address bar, leaving out the 
		 * 												$to_unset variable
		 * 
		 * @param $to_unset
		 * @return 
		 */
		function rebuild($to_unset="block"){
			global $_GET;
			unset($_GET[$to_unset]);
				
			$glue = "?";
			reset($_GET);
			while(list($key,$val)=each($_GET)){
				$val = str_replace(" ","%20",$val);
				$glue .= $key . "=" . $val . "&";
			}
			return $glue;
		}//end function
		
		
		/**
		 * pageSplit::setDesign() - prepare design template
		 * 
		 * @param $body
		 * @param $header
		 * @param $footer
		 * @return 
		 */
		function setDesign($body='<a href={link}>{text}</a>',$body_current='<b>{text}</b>',$separator=', ',$header='',$footer=''){
			$this->body = $body;
			$this->header = $header;
			$this->footer = $footer;
			$this->separator = $separator;
			$this->body_current = $body_current;			
		}
		
		/**
		 * pageSplit::setBody() - set the body of every entity (desing template)
		 * 												must have a {link} and a {text} tags
		 * 
		 * @param $body
		 * @param $body_current
		 * @return 
		 */
		function setBody($body='<a href={link}>{text}</a>',$body_current='<b>{text}</b>'){
			$this->body = $body;
			$this->body_current = $body_current;
		}
		
		/**
		 * pageSplit::setSeparator() - used to separate various entities...
		 * 
		 * @param $sep
		 * @return 
		 */
		function setSeparator($sep = ', '){
			$this->separator = $sep;
		}
		
		/**
		 * pageSplit::setSlider() - the number of elements total to slide in
		 * 
		 * @param $slider
		 * @return 
		 */
		function setSlider($slider=7){
			$this->slider = $slider;
		}
		
		/**
		 * pageSplit::setPN() - DESIGN - set PREVIOUS and NEXT messages
		 * 
		 * @param $prev
		 * @param $next
		 * @return 
		 */
		function setPN($prev="Prev",$next="Next"){
			$this->prev = $prev;
			$this->next = $next;
		}
		
		/**
		 * pageSplit::showNav() - actual processing
		 * 
		 * @return 
		 */
		function out(){
			//initialize
			$elements = array();
			$rebuid = $this->rebuild();
			
			//find out total number of blocks
			$repetitions = ceil($this->max_elements / $this->block_size);
			
			//adjust them to the loops to fit the slider
			if($repetitions<=$this->slider){
				//okay, the total number of elements is less than needed for a slider
				$start_loop = 0;
				$end_loop = $repetitions;
			}else{
				//wow, we need to figure out how to slide through the elements
				
				//this is the mid point of the slider
				$mid_point = floor($this->pointer / $this->block_size);
				
				$my_step = $this->slider / 2; //step from the mid point
				$step_left = floor($my_step);	//step to the left
				$step_right = ceil($my_step);	//step to the right
				
				$start_loop = $mid_point - $step_left;	//starting block of data
				$end_loop = $mid_point + $step_right;		//ending block of data
				
				//error check, slider correction
				if($start_loop < 0){	//cannot be a negative block ;)
					$end_loop = $end_loop + abs($start_loop);	//if it is, add the excess to the end
					$start_loop = 0;
				}
				
				if($end_loop>$repetitions){	//cannot have more elements than there is!
					$start_loop = $start_loop - $end_loop + $repetitions;	//if it does, add to the beginning
					$end_loop = $repetitions;
				}						
			}
					
			//OOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO	
			//output header
			//OOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO
			$out = $this->header;
			
			//OOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO	
			//output previous
			//OOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO
			if($start_loop > 0){
				$temp = str_replace("{text}",$this->prev,$this->body);
				$my_link = $rebuid . "block=" . ($this->pointer - $this->block_size);
				$out .= str_replace("{link}",$this->link . $my_link,$temp) . $this->separator;
			}
			
			//OOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO	
			//output contents
			//OOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO
			for($x=$start_loop;$x<$end_loop;$x++){
				$start_link = ($x * $this->block_size) + 1;
				$end_link = ($x * $this->block_size) + $this->block_size;
				
				if($end_link>$this->max_elements){
					$end_link = $this->max_elements;
				}
				
				//get the correct degign template
				if((($this->pointer + 1)>$start_link) and ($this->pointer<=$end_link)){
					//selected
					$temp = str_replace("{text}",$start_link . '-' . $end_link,$this->body_current);
				}else{
					//possible to navigate to
					$temp = str_replace("{text}",$start_link . '-' . $end_link,$this->body);
					$my_link = $rebuid . "block=" . (($x * $this->block_size) + 1);
					$temp = str_replace("{link}",$this->link . $my_link,$temp);
				}				
				$elements[$x] = $temp;
			}
			
			$out .= implode($this->separator,$elements);
			
			//OOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO	
			//output next
			//OOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO
			if($end_loop < $repetitions){
				$temp = str_replace("{text}",$this->next,$this->body);
				$my_link = $rebuid . "block=" . ($this->pointer + $this->block_size);
				$out .= $this->separator . str_replace("{link}",$this->link . $my_link,$temp);
			}
			
			//OOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO	
			//output footer
			//OOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO			
			$out .= $this->footer;
			
			//don't out only one resulting navigation element
			if(count($elements)<2){
				$out = '';
				return FALSE;
			}
			
			return $out;
		}//end function
  }//end class pageSplit
?>
