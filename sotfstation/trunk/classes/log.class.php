<?
	class logBuilder{
		/**
		 * logBuilder::logBuilder()
		 * 
		 * constructor
		 * 
		 * @return 
		 */
		function logBuilder(){
		}
		
		/**
		 * logBuilder::add()
		 * 
		 * adds a log entry to the database
		 * 
		 * @param $owner (int)
		 * @param $action (int)
		 * @return 
		 */
		function add($owner,$action){
			global $db;
			if((ALLOW_LOGS) and is_int($action)){
				$db->query("INSERT INTO user_log(auth_id,action) VALUES('$owner','$action')");
				return true;
			}
			return false;
		}
	}
?>