<?	
	/**********************
	* Class: User
	* Purpose: To handle all the user data to store an object as a
	* 				 session variable
	* Author: Koulikov Alexey -- alex@koulikov.cc, alex@pvl.at
	* Vesrion: 1.0
	* 
	* eg. use:
	* ------------------------------------------------------------------------------
	* 	$myUser = new User(1512);
	* 	$myUser->set("name","Alex");
	* 	$myUser->set("access_level","7");
	* 
	* 	echo "User ID: " . $myUser->get_user_id() . "<br>";
	* 	echo "User Name: " . $myUser->get("name") . "<br>";
	* 	echo "User Access Level: " . $myUser->get("access_level") . "<br>";
	* 
	* 	if($myUser->get("gender")){
	* 		echo "User Gender: " . $myUser->get("gender");
	* 	}else{
	* 		echo "No User Gender Has Been Set";
	* 	}
	* ------------------------------------------------------------------------------ 
	******/
	class user{
		var $user_id;
		var $user_props;
		
		/********
		* Constructor
		***/
		function user($user_id="default"){
			$user_name = $this->set_user_id($user_id);
			$user_props = array();
		}
		
		
		/**
		 * user::set_user_id()
		 * 
		 * SETS the name of the user (user id)
		 * 
		 * @param $to_set
		 * @return TRUE
		 */
		function set_user_id($to_set){
			$this->user_name=$to_set;
			return TRUE;
		}
		
		
		/**
		 * user::get_user_id()
		 * 
		 * GETS the id of the user (user_id)
		 * 
		 * @return (string)
		 */
		function get_user_id(){
			return $this->user_name;
		}
		
		
		/**
		 * user::set()
		 * 
		 * ADDS some property to the user, overwriting existing values
		 * if they do exist!
		 * 
		 * @param $prop_to_set
		 * @param $value
		 * @return TRUE
		 */
		function set($prop_to_set,$value){
			$this->user_props[$prop_to_set] = $value;
			return TRUE;
		}
		
		
		/**
		 * user::get()
		 * 
		 * GETS the value of some user property, returning false if no
		 * value has been set
		 * 
		 * @param $prop_to_get
		 * @return (string)||FALSE
		 */
		function get($prop_to_get){
			if(isset($this->user_props[$prop_to_get])){
				return $this->user_props[$prop_to_get];
			}else{
				return FALSE;
			}
		}
		
		
		/**
		 * user::is_set()
		 * 
		 * Returns TRUE if a property has been set, and false otherwise
		 * 
		 * @param $prop_to_get
		 * @return (bool)
		 */
		function is_set($prop_to_get){
			if(isset($this->user_props[$prop_to_get])){
				return TRUE;
			}else{
				return FALSE;
			}
		}
		
		
		/**
		 * user::unset()
		 * 
		 * To unset any user property
		 * 
		 * @param $prop_to_uset
		 * @return TRUE
		 */
		function unset_prop($prop_to_uset){
			unset($this->user_props[$prop_to_unset]);
			return TRUE;
		}
		
		
		/**
		 * user::unsetAll() - to unset ALL user properties
		 * 
		 * @return TRUE
		 */
		function unsetAll(){
			$this->user_props = array();
			return TRUE;
		}
		
		
		/**
		 * user::setAll() - set the whole property array at once
		 * 
		 * @param $props (array)
		 * @return (bool)
		 */
		function setAll($props){
			if(is_array($props)){
				$this->user_props = array_merge($this->user_props,$props);
				return true;
			}
			return false;
		}
	}
?>