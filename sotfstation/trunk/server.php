<?
	/**
 	* XML-RPC STATION SERVER
 	******/
	
	include("xmlrpc/xmlrpc.inc");
	include("xmlrpc/xmlrpcs.inc");
	include('config.inc.php');													# guest what this is ;)
	require(PEAR_PATH . 'DB.php');												# Pear DB Object .::. http://pear.php.net/
	
	$db=DB::connect("pgsql://" . DB_USER . ":" . DB_PASS . "@" . DB_HOST . "/" . DB_NAME);

	
	/**
	 * schedule() Schedule will return a three element associative array 
	 * 			  with the current, previous and next shows to be played 
	 *            by the station server.
	 * 
	 * @return object
	 **/
	function schedule(){
		global $db;
		
		$toReturn = array();
		
		//first get the programme that is running NOW!
		if(!$toReturn['current'] = $db->getRow("SELECT 
									programme.id AS programme_id,
									programme.intime AS programme_start,
									programme.outtime AS programme_end,
									programme.title AS programme_title,
									programme.special AS programme_special,
									programme.description AS programme_description,
									programme.topic AS programme_topic,
									programme.genre AS programme_genre,
									series.id AS series_id,
									series.title AS series_title,
									series.description AS series_description
								 FROM programme LEFT JOIN series ON (programme.series_id = series.id) WHERE programme.intime < '".date("Y-m-d H:i:s")."' AND programme.outtime > '".date("Y-m-d H:i:s")."' ORDER BY programme.intime ASC LIMIT 1",DB_FETCHMODE_ASSOC)){
			
			$toReturn['current'] = false;
			$startLimiter = date("Y-m-d H:i:s");
			$endLimiter = $startLimiter;
		}else{
			$startLimiter = $toReturn['current']['programme_start'];
			$endLimiter = $toReturn['current']['programme_end'];
		}
		
		//get previous programme
		if(!$toReturn['prev'] = $db->getRow("SELECT 
									programme.id AS programme_id,
									programme.intime AS programme_start,
									programme.outtime AS programme_end,
									programme.title AS programme_title,
									programme.special AS programme_special,
									programme.description AS programme_description,
									programme.topic AS programme_topic,
									programme.genre AS programme_genre,
									series.id AS series_id,
									series.title AS series_title,
									series.description AS series_description
								 FROM programme LEFT JOIN series ON (programme.series_id = series.id) WHERE programme.outtime < '".$startLimiter."' ORDER BY programme.outtime DESC LIMIT 1",DB_FETCHMODE_ASSOC)){
								 
			$toReturn['prev'] = false;
		}
		
		//get next programme
		if(!$toReturn['next'] = $db->getRow("SELECT 
									programme.id AS programme_id,
									programme.intime AS programme_start,
									programme.outtime AS programme_end,
									programme.title AS programme_title,
									programme.special AS programme_special,
									programme.description AS programme_description,
									programme.topic AS programme_topic,
									programme.genre AS programme_genre,
									series.id AS series_id,
									series.title AS series_title,
									series.description AS series_description
								 FROM programme LEFT JOIN series ON (programme.series_id = series.id) WHERE programme.intime > '".$endLimiter."' ORDER BY programme.intime ASC LIMIT 1",DB_FETCHMODE_ASSOC)){
								 
			$toReturn['prev'] = false;
		}
		
		//return data array
		return new xmlrpcresp(xmlrpc_encode($toReturn));
	}
	
	
	
	/**
	 * shift()
	 * 
	 * @param $parameters
	 * @return 
	 **/
	function shift($parameters){
		global $db;
		
		$shift = xmlrpc_decode($parameters->getParam(0));
		
		//allright, if we need to shift, then shift the while programme block by the defined value
		if($shift!=0){	//shift up
			
			//calculate start and end times of upcoming programme block
			$start = $db->getRow("SELECT intime, outtime FROM programme WHERE intime > '".date("Y-m-d H:i:s")."' ORDER BY intime ASC LIMIT 1",DB_FETCHMODE_ASSOC);
			$out = $start['outtime'];
			
			do{
				if($end = $db->getRow("SELECT intime, outtime FROM programme WHERE intime = '".$out."'",DB_FETCHMODE_ASSOC)){
					$out = $end['outtime'];
					$tempIn = $end['intime'];
				}
			}while($end);
			
			//check if I can shift by that fraction
			if($shift > 0){
				if($nextShow = $db->getRow("SELECT intime, outtime FROM programme WHERE intime > '".$out."' ORDER BY intime ASC LIMIT 1",DB_FETCHMODE_ASSOC)){
					if(($shift * 60) > (strtotime($nextShow['intime']) - strtotime($out))){
						return new xmlrpcresp(0, 1, "Cannot shift by that many minutes, since the next free timeblock is not big enough!");
					}
				}
			}
			
			//shift the block by $shift minutes
			$reply = $db->getOne("SELECT count(*) FROM programme WHERE intime >= '$start[intime]' AND outtime <= '$out'");
			$db->query("UPDATE programme SET intime = intime + interval'$shift minutes', outtime = outtime + interval'$shift minutes' WHERE intime >= '$start[intime]' AND outtime <= '$out'");
		}else{
			$reply = false;
		}
		
		//return data array
		return new xmlrpcresp(xmlrpc_encode($reply));
	}
	
	
	//create the actual server
	$authorize_sig[0] = array(array('struct'));
	$authorize_doc[0] = "Schedule will return a three element associative array with the current, previous and next shows to be played by the station server.";
	
	$authorize_sig[1] = array(array('struct','int'));
	$authorize_doc[1] = "Schedule will return a three element associative array with the current, previous and next shows to be played by the station server.";
										
	new xmlrpc_server(array(
							"station.schedule" => 
											array(	"function"=>"schedule",
													"signature"=>$authorize_sig[0],
													"docstring"=>$authorize_doc[0]
												),
							"station.shift"	=>
											array(	"function"=>"shift",
													"signature"=>$authorize_sig[1],
													"docstring"=>$authorize_doc[1]
												)
							)
					);

?>