<?
	$RPCCONF['SERVER'] = 'localhost';						# Location of XMLRPC SERVER, eg. www.domain.com
	$RPCCONF['DISPOS'] = '/work/sotfstation/server.php';	# Location of the server file eg. /server.php
	
	//include libraries
	include("xmlrpc/xmlrpc.inc");
	
	//create client
	$client = new xmlrpc_client($RPCCONF['DISPOS'],$RPCCONF['SERVER'],80);
	//$client->setDebug(1);
	
	//create message for server				
	$message = new xmlrpcmsg('station.shift',array(xmlrpc_encode(0)));
							
	$response = $client->send($message);

	//check if we have a response
	if(!$response){
		// here, I handle the situation where no valid connection could be built
		// to the RPC server. Currently it is just a textual message, but you
		// are free to do whatever you want!
		echo "No Connection to the specified RPC Server!";
	}else{
		if($response->value()){	//no error has occured, yahoo!
			$val = xmlrpc_decode($response->value());
			 
			// now, here it is, the array $val contains the whole data associated
			// with the specified action...
			// 
			// The contents of the array can be viewed as:
			// 
			echo "<pre>";
			print_r($val);
			echo "</pre>";
		}else{
			// this part of code happens whenever there is an invalid match
			// i.e. either the user does not exist or the password is wrong.
			echo "FAULT: " . $response->faultString() . ", code: " . $response->faultCode() . "<br>\n";
		}
	}
?>