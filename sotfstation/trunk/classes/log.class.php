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
			if((ALLOW_LOGS) and (is_int($owner) and (is_int($action)))){
				$time = date("Y-m-d H:i:s");
				$db->query("INSERT INTO user_log(auth_id,action,intime) VALUES('$owner','$action','$time')");
				return true;
			}
			return false;
		}
	}
?>