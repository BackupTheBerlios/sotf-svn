<?
	/**************
	* Search your Database using a parser, great work :)
	* Author: Koulikov Alexey - alex@pvl.at, alex@koulikov.cc
	* Date: 26.07.2002
	***************
	* This class will make use of the PEAR DB abstration layer
	* but can easily work whithout it, just make sure you don't
	* call the find() but just take the SQL produced by this class
	* and pass it to your database engine.
	* 
	* This class can easily construct a query of ANY complexity to
	* make a fast search thrugh your database. You can specify
	* fields to be searched in, tables to be linked, group results,
	* sort result and limit the possible output set.
	* 
	* A Simple Examle:
	* 
	* $mySniff = new sniffer($pear_db);
	*	$mySniff->search("god","sotf_items","track, station, owner");	
	*	echo "<pre>";
	* print_r($mySniff->find());
	* echo "</pre>";
	* 
	* will output to the screen a result set containing ALL the
	* info about sotf_items wither owned by god, or his name
	* being anywhere in the track name or station name.
	* 
	* This search class works well with GOOGLE LIKE token, i.e.
	* 
	* if the search string is set to 'g -d', then all the sotf_items
	* will be selected that have the owner starting with a letter G 
	* but NOT having a letter d anywhere inside the string
	* 
	* if you want to limit the output set, just pass the desired fields
	* to the setSelect() method, by default it is '*', if you just need
	* the station and the owner, call
	* 
	* $mySniff->select("station, owner");
	* 
	* the fields to be looked in you set by
	* 
	* $mySniff->fields("owner, track");
	* 
	* you can limit the output set by setting the upper and the lower
	* boundaries by user limitStart() and limitEnd().
	* 
	* you can sort the set by using order()
	* 
	* and group the set by using group()
	* 
	* in case you would like to construct a search query with additional
	* constraints, like say a date range, you just pass these to the
	* constraint() method, for eg
	* 
	* $mySniff->constraint("entered > '2002-01-01' AND entered < '2002-06-01'");
	* 
	* And of course use debug() to see what is going on inside the class :)
	*****************************/
	
	/***** THIS IS MINE, YOU CAN CUT THIS OUT *********
	require_once 'DB.php';
		
	$CONFIG['DB_USER'] = 'Dolce';				//database user
	$CONFIG['DB_PASS'] = ''; 						//user's password
	$CONFIG['DB_HOST'] = 'localhost'; 	//database host
	$CONFIG['DB_NAME'] = 'dolce';				//database name
	
	$dsn = "pgsql://$CONFIG[DB_USER]:$CONFIG[DB_PASS]@$CONFIG[DB_HOST]/$CONFIG[DB_NAME]"; 
	$pear_db = DB::connect($dsn, false); 	
	*/
	
	class sotf_Search {
		var $db;
		var $limitStart = 0;
		var $limitEnd;
		var $table;
		var $fields = array();
		var $constrainsts = array();
		var $order = array();
		var $select = array("*");
		var $group;
		var $searchStrng;
		var $sql;
		var $error;
		
		
		/**
		 * sniffer::sniffer()
		 * 
		 * purpose: el constructor
		 * 
		 * @param $db_handle (object)
		 * @return (void)
		 */
		function sotf_Search($db_handle){
			$this->db = $db_handle;
		}
		
		
		/**
		 * sniffer::searchString()
		 * 
		 * purpose: to set a search string
		 * 
		 * @param $search_string (string)
		 * @return (void)
		 */
		function searchString($search_string){
			$this->searchString = htmlspecialchars(trim($search_string));			
		}
		
		
		/**
		 * sniffer::table()
		 * 
		 * purpose: set the table name(s) to look in
		 * 
		 * @param $table (string)
		 * @return (void)
		 */
		function table($table){
			$this->table = $table;
		}
		
		
		/**
		 * sniffer::fields()
		 * 
		 * purpose: set the fields array
		 * 
		 * @param $fields (string)
		 * @return (void)
		 */
		function fields($fields){
			$fields = str_replace("\n","",$fields);
			$fields = str_replace(" ","",$fields);
			$this->fields = explode(",",$fields);
		}
		
		
		/**
		 * sniffer::search()
		 * 
		 * purpose: set all parameters at once
		 * 
		 * @param $search_string (string)
		 * @param $table_name (string)
		 * @param $field_names (string)
		 * @param $limit_start (int)
		 * @param $limit_end (int)
		 * @return (void)
		 */
		function search($search_string,$table_name,$field_names,$limit_start="",$limit_end=""){
			$this->searchString($search_string);
			$this->table($table_name);
			$this->fields($field_names);
			$this->limitStart($limit_start);
			$this->limitEnd($limit_end);
		}
		
		
		/**
		 * sniffer::limitStart()
		 * 
		 * purpose: set the start limit pointer
		 * 
		 * @param $limit_start (int)
		 * @return (void)
		 */
		function limitStart($limit_start){
			if(!empty($limit_start)){
				$this->limitStart=$limit_start;
			}
		}
		
		
		/**
		 * sniffer::limitEnd()
		 * 
		 * purpose: set the end limit pointer
		 * 
		 * @param $limit_end (int)
		 * @return (void)
		 */
		function limitEnd($limit_end){
			if(!empty($limit_end)){
				$this->limitEnd=$limit_end;
			}
		}
		
		
		/**
		 * sniffer::constraint()
		 * 
		 * purpose: set constraints
		 * 
		 * @param $constraint (string)
		 * @return (void)
		 */
		function constraint($constraint){
			$this->constraints = explode(" AND ",str_replace("\n","",$constraint));
		}
		
		
		/**
		 * sniffer::orderBy()
		 * 
		 * purpose: set an order by parameter
		 * 
		 * @param $order (string)
		 * @return (void)
		 */
		function order($order){
			$order = str_replace("\n","",$order);
			$order = str_replace(" ","",$order);
			$this->order = explode(",",$order);
		}
		
		
		/**
		 * sniffer::group()
		 * 
		 * purpose: to set a group by parameter
		 * 
		 * @param $group (string)
		 * @return (void)
		 */
		function group($group){
			$this->group = $group;
		}
		
		
		/**
		 * sniffer::setSelect()
		 * 
		 * purpose: what do we want to select?
		 * 
		 * @param $select (string)
		 * @return 
		 */
		function select($select){
			$select = str_replace("\n","",$select);
			$select = str_replace(" ","",$select);
			$this->select = explode(",",$select);
		}
		
		
		/**
		 * sniffer::find()
		 * 
		 * purpse: the actual call to the database, returning an array of values
		 * 
		 * @return 
		 */
		function find(){
			//not yet :)
			$this->makeSQL();
			$res = $this->db->getAll($this->sql,DB_FETCHMODE_ASSOC);
			if(DB::isError($res)){
				trigger_error($res->getMessage());
			}
			if(!$res){
				return false;
			}
			return $res;
		}
		
		
		/**
		 * sniffer::makeSQL()
		 * 
		 * purpose: to create the actual sql
		 * 
		 * @return 
		 */
		function makeSQL(){
			$search = $this->getSearchString();
			$fields = $this->getFields();
			
			if($search!=""){
				//build array from input..
				$params = split(" ", $search);
				$InQuotedString = 0;
          
				//now, build tokens from array (watch the "")
				$tokNum = 0;
				$tokens = array();
          
				$tokens[$tokNum] = "";
				for($i=0;$i<count($params);$i++){
					if(isset($tokens[$tokNum])){
						$tokens[$tokNum] = "";
					}

					$param = $params[$i];
					if(ereg("^\"",$param) || ereg("^[+-]\"",$param)){
        		$InQuotedString = 1;
      		}
            
					if($InQuotedString==1){
						$tokens[$tokNum] .= ereg_replace("\"","",$param) . " ";
					}else{
						$tokens[$tokNum++] = $param;
					}
    
					if(ereg("\"$", $param)){
						$InQuotedString = 0;
						$tokens[$tokNum] = chop($tokens[$tokNum]);
						$tokNum++;
					}      
				}//end for                
          
				//build SQL
				$SQL = "";								

				for($i=0; $i<count($tokens); $i++){
					for($x=0; $x<count($fields); $x++){
						$token = ereg_replace(" $", "", $tokens[$i]);
						if(ereg("^\\+",$token)){
							$token = ereg_replace("^\\+","",$token);
							$SQL .= "$fields[$x] like '%$token%'";
							if($x<count($fields)-1){
								$SQL .= " OR ";
							}
						}elseif(ereg("^\\-",$token)){
							$token = ereg_replace("^\\-","",$token);
							$SQL .= "$fields[$x] NOT like '%$token%'";
							if($x<count($fields)-1){
								$SQL .= " AND ";
							}
						}else{
							$SQL .= "$fields[$x] like '%$token%'";
							if($x<count($fields)-1){
								$SQL .= " OR ";
							}
						}
      		}//end inner for
    
					if($i<count($tokens)-1){
						$SQL .= ") AND (";
					}else{
						$SQL .= ")";
					}
    		}//end outer for
				
				//check constraints
				if($this->getConstraints()){
					$SQL .= " AND " . $this->getConstraints(1);
				}
												
				//check groups
				if($this->getGroup()){
					$SQL .= " GROUP BY " . $this->getGroup();
				}
				
				//check order by
				if($this->getOrder()){
					$SQL .= " ORDER BY " . $this->getOrder(1);
				}
				
				//check limits
				if($this->getLimitEnd()){
					$SQL .= " LIMIT " . $this->getLimitStart() . ", " . $this->getLimitEnd();
				}
				
				$SQL = "SELECT " . $this->getSelect(1) . " FROM " . $this->getTable() . " WHERE (" . $SQL;
				
				$this->sql = $SQL;
				return $SQL;
				
  		}else{
				//check constraints
				if($this->getConstraints()){
					$SQL .= " WHERE " . $this->getConstraints(1);
				}
												
				//check groups
				if($this->getGroup()){
					$SQL .= " GROUP BY " . $this->getGroup();
				}
				
				//check order by
				if($this->getOrder()){
					$SQL .= " ORDER BY " . $this->getOrder(1);
				}
				
				//check limits
				if($this->getLimitEnd()){
					$SQL .= " LIMIT " . $this->getLimitStart() . ", " . $this->getLimitEnd();
				}
				
				$SQL = "SELECT " . $this->getSelect(1) . " FROM " . $this->getTable() . $SQL;
				$this->sql = $SQL;
				return $SQL;
			}
		}
		
		
		/**
		 * sniffer::getSQL()
		 * 
		 * purpose: to return the last generated SQL query
		 * 
		 * @return (string)
		 */
		function getSQL(){
			return $this->sql;
		}
		
		
		/**
		 * sniffer::getSearchString()
		 * 
		 * purpose: to return the string that is being searched for
		 * 
		 * @return (string)
		 */
		function getSearchString(){
			return $this->searchString;
		}
		
		
		/**
		 * sniffer::getTable()
		 * 
		 * purpose: to return the tables that are searched
		 * 
		 * @return (string)
		 */
		function getTable(){
			return $this->table;		
		}
		
		
		/**
		 * sniffer::getFields()
		 * 
		 * purpose: to return all the fields undergoing the search,
		 * 					either as an array or as a string
		 * 					default type will return an array
		 * 					type 1 will return a string
		 * 					all other types will return false
		 * 
		 * @param $type
		 * @return (array)(string)
		 */
		function getFields($type=0){
			if($type==0){
				return $this->fields;
			}else if($type==1){
				return implode(", ",$this->fields);
			}else{
				return false;
			}
		}
		
		
		/**
		 * sniffer::getLimitStart()
		 * 
		 * purpose: to return the starting pointer of the limit by constraints
		 * 
		 * @return (int)
		 */
		function getLimitStart(){
			return $this->limitStart;
		}
		
		
		/**
		 * sniffer::getLimitEnd()
		 * 
		 * purpose: to return the limiting factor
		 * 
		 * @return (int)
		 */
		function getLimitEnd(){
			return $this->limitEnd;
		}
		
		
		/**
		 * sniffer::getConstraints()
		 * 
		 * purpose: return either an array or a string of possible
		 * 					additional constraints.
		 * 
		 * 					default type will return array
		 * 					type 1 will return a string
		 * 					all other types will return false
		 * 
		 * @param $type
		 * @return (array)(string)
		 */
		function getConstraints($type=0){
			if($type==0){
				return $this->constraints;
			}else if($type==1){
				return implode(" AND ",$this->constraints);
			}else{
				return false;
			}
		}
		
		
		/**
		 * sniffer::getGroup()
		 * 
		 * purpose: to return the group by parameter
		 * 
		 * @return (string)
		 */
		function getGroup(){
			return $this->group;
		}
		
		
		/**
		 * sniffer::getOrder()
		 * 
		 * purpose: to return the order by parameter list, either
		 * 					as an array or as a string
		 * 					type 0 will return an array
		 * 					type 1 will return a string
		 * 
		 * @param $type
		 * @return (array)(string)
		 */
		function getOrder($type=0){
			if($type==0){
				return $this->order;
			}else if($type==1){
				return implode(" AND ",$this->order);
			}else{
				return false;
			}
		}
		
		
		/**
		 * sniffer::getSelect()
		 * 
		 * purpose: to return the select by parameter list, either
		 * 					as an array or as a string
		 * 					type 0 will return an array
		 * 					type 1 will return a string
		 * 
		 * @param $type
		 * @return (array)(string)
		 */
		function getSelect($type=0){
			if($type==0){
				return $this->select;
			}else if($type==1){
				return implode(", ",$this->select);
			}else{
				return false;
			}
		}
		
		
		/**
		 * sniffer::error()
		 * 
		 * purpose: return the last set error
		 * 
		 * @return (string)
		 */
		function error(){
			return $this->error;
		}
		
		
		/**
		 * sniffer::debug()
		 * 
		 * purpose: show the insides of the class
		 * 
		 * @return (echo)
		 */
		function debug(){
			echo "======================= SEARCH DEBUG ===========================<br>";
			
			echo "<b>Database Handle:</b> ";
			if(empty($this->db->dsn)){
				echo $this->db->message . "<br>";
			}else{
				echo "<pre>";
				print_r($this->db->dsn);
				echo "</pre>";
			}			
			
			echo "<b>Searching For:</b> " . $this->getSearchString() . "<br>";
			echo "<b>Searching In:</b> " . $this->getTable() . "<br>";
			echo "<b>Searching in Fields:</b> ";

			$fields = $this->getFields();
			if(!empty($fields)){
				echo "<pre>";
				print_r($this->getFields());
				echo "</pre>";
			}else{
				echo "<br>";
			}
			
			echo "Selecting:</b> ";
			$fields = $this->getSelect();
			if(!empty($fields)){
				echo "<pre>";
				print_r($this->getSelect());
				echo "</pre>";
			}else{
				echo "<br>";
			}
			
			echo "<b>Using Constraints:</b> ";
			$fields = $this->getConstraints();
			if(!empty($fields)){
				echo "<pre>";
				print_r($this->getConstraints());
				echo "</pre>";
			}else{
				echo "<br>";
			}
			
			echo "<b>Group By:</b> " . $this->getGroup() . "<br>";
			
			echo "<b>Order By:</b> ";
			$order = $this->getOrder();
			if(!empty($order)){
				echo "<pre>";
				print_r($this->getOrder());
				echo "</pre>";
			}else{
				echo "<br>";
			} 
			
			echo "<b>Limit Start:</b> " . $this->getLimitStart() . "<br>";
			echo "<b>Limit End:</b> " . $this->getLimitEnd() . "<br>";					
			 
			echo "<b>Last SQL Query Constructed:</b> " . $this->getSQL() . "<br>";
			echo "<b>Last Error:</b> " . $this->error();
		}		
	}
	
	/*
	$mySniff = new sniffer($pear_db);
	$mySniff->search("","sotf_items","track, station, owner");	
	echo "<pre>";
	print_r($mySniff->find());
	echo "</pre>";
	//$mySniff->debug();
	*/
?>