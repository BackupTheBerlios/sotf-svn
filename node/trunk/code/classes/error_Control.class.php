<?
		
	/***
	* Error Control Class V1.0
	**********/
	
	class error_Control {

		var $e_list = array();
		
		/**
		 * error::error() - constructor
		 * 
		 * @return void
		 * 
		 * Koulikov Alexey 31.01.2002 - alexey@koulikov.cc
		 */
		function errorControl(){
			//nothing happens here... yet ;)
		}
						
		/**
		 * error::addError() - add an error to the end of the list
		 * 
		 * @param $e_message
		 * @return void
		 * 
		 * Koulikov Alexey 31.01.2002
		 */
		function add($e_message) {
			array_push($this->e_list, $e_message);
      error_log("StreamOnTheFly error: $msg", 0);
		}

		function raise($e_message) {
      global $page;
			array_push($this->e_list, $e_message);
      error_log("StreamOnTheFly error: $msg", 0);
      $page->halt();
      exit;
		}
		
		/**
		 * error::getError() - returns a given error
		 * 
		 * @param $n
		 * @return string
		 * 
		 * Koulikov Alexey 31.01.2002
		 */
		function getError($n=0){
			return $this->e_list[$n];
		}
		
		/**
		 * error::getLast() - returns the last added error
		 * 
		 * @return 
		 */
		function getLast(){
			$max = count($this->e_list) - 1;
			return $this->e_list[$max];
		}
		
		/**
		 * error::checkLength() - will check whether a string is of minimal length
		 * 
		 * @param $some_string
		 * @param $sentinel
		 * @return bool
		 * 
		 * Koulikov Alexey 31.01.2002
		 */
		function checkLength($some_string,$sentinel=0){
			if(strlen($some_string)<=$sentinel){
				return FALSE;
			}else{
				return TRUE;
			}
		}
		
		/**
		 * error::checkMail() - will check whether the string is a valid e-mail address
		 * 
		 * @param $some_string
		 * @return bool
		 * 
		 * Koulikov Alexey 31.01.2002
		 */
		function checkMail($some_string){
			if (eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*$",$some_string)){
				return TRUE;
			}
      return FALSE;
		}
		
		/**
		 * error::checkUser() - to check whether a valid user name has been choosen
		 * 
		 * @param $some_string
		 * @return bool
		 * 
		 * Koulikov Alexey 31.01.2002
		 */
		function checkUser($some_string,$sentinel=4){
			$some_string = str_replace(".","",$some_string);
			if (eregi("^[_a-z0-9-]{" . $sentinel . ",32}$",$some_string)){
				return TRUE;
			}
      return FALSE;
		}
		
		/**
		 * error::checkDIP()
		 * 
		 * @param $some_string
		 * @param $sentinel
		 * @return 
		 */
		function checkDIP($some_string,$sentinel=2){
			if(strlen($some_string)<$sentinel){
				return FALSE;
			}
			
			if (eregi("^[_a-z0-9-]([_a-z0-9-]|\.)*[_a-z0-9-]$",$some_string)){
				return TRUE;
			}
      return FALSE;
		}
		
		
		/**
		 * error::checkInt() - will check wether the passed parameter is an integer
		 * 
		 * @param $some_int
		 * @return bool
		 * 
		 * Koulikov Alexey 07.02.2002
		 */
		function checkInt($some_int,$length=0){
			if(!ereg("^[0-9]{" . $length . ",14}$",$some_int)){
		  	return FALSE;
	    }else{
      	return TRUE;
      }
		}
		
		
		/**
		 * error::checkInt() - will check wether the passed parameter is a float
		 * 
		 * @param $some_int
		 * @return bool
		 * 
		 * Koulikov Alexey 07.02.2002
		 */
		function checkFloat($some_float,$length=0){
			if(!ereg("^[0-9.,]{" . $length . ",14}$",$some_float)){
		  	return FALSE;
	    }else{
      	return TRUE;
      }
		}
		
		
		/**
		 * error::getList() - will return the list of all errors on request
		 * 
		 * @return array()
		 * 
		 * Koulikov Alexey 31.01.2002
		 */
		function getList(){	
			return $this->e_list;
		}
		
		/**
		 * error::getLength() - return how many errors are in the list
		 * 
		 * @return int
		 * 
		 * Koulikov Alexey 31.01.2002
		 */
		function getLength(){
			return count($this->e_list);
		}
		
		/**
		 * cleanInput() - get rid of any unwanted spaces ;)
		 * 
		 * @param $some_input
		 * @return $some_input - array
		 * 
		 * Koulikov Alexey :: 31.01.2002	
		 */
		function cleanInput($some_input){
			while(list($key,$val) = each($some_input)){
				$some_input[$key] = trim(htmlspecialchars($val));
			}
			return $some_input;
		}
	}
?>