<?
		require('xmlrpc/xmlrpc.inc');
	
		//Create a client
		$xmlrpc_client = new xmlrpc_client('/node/www/xmlrpcServer.php', 'sotf2.dsd.sztaki.hu', 80);
		//$xmlrpc_client->setDebug(1);
		
		####### GET TOPICS #######
		$data[] = new xmlrpcval("topics",'string');
		$data[] = new xmlrpcval(1,'int');
		$data[] = new xmlrpcval("en",'string');
		$message = new xmlrpcmsg('sotf.cv.get',$data);
		
		//send request
		$response = $xmlrpc_client->send($message);
	
		//process response
		if($response->value()){	# great, all worked out!
			$response = xmlrpc_decode($response->value());
			
			$file = fopen('templates/topics.txt', "w");
			foreach($response as $topic){
				
				$string = $topic['id'] . ";" . substr(urldecode($topic['name']),0,48) . ";" . $topic['level'] . "\n";
				fputs($file,$string);
			}
			fclose($file);
			echo "!!Processed Topic Tree!!<br>\n";
			
		}elseif($response->faultCode()){ # bad response   
			echo "Topic Tree: A faultcode has been returned<br>\n";
		}else{	# server is busy
			echo "Topic Tree: No Connection To Server<br>\n";
		}
		
		####### GET GENRES #######
		$data = array();
		$data[] = new xmlrpcval("genres",'string');
		$data[] = new xmlrpcval(1,'int');
		$data[] = new xmlrpcval("en",'string');
		$message = new xmlrpcmsg('sotf.cv.get',$data);
		
		//send request
		$response = $xmlrpc_client->send($message);
	
		//process response
		if($response->value()){	# great, all worked out!
			$response = xmlrpc_decode($response->value());
			$file = fopen('templates/genres.txt', "w");
			foreach($response as $genre){
				$string = $genre['id'] . ";" . substr(urldecode($genre['name']),0,48) . "\n";
				fputs($file,$string);
			}
			fclose($file);
			echo "!!Processed Genre Tree!!<br>\n";
			
		}elseif($response->faultCode()){ # bad response   
			echo "Genre Tree: A faultcode has been returned";
		}else{	# server is busy
			echo "Genre Tree: No Connection To Server";
		}
		
		
		####### GET ROLES #######
		$data = array();
		$data[] = new xmlrpcval("roles",'string');
		$data[] = new xmlrpcval(1,'int');
		$data[] = new xmlrpcval("en",'string');
		$message = new xmlrpcmsg('sotf.cv.get',$data);
		
		//send request
		$response = $xmlrpc_client->send($message);
	
		//process response
		if($response->value()){	# great, all worked out!
			$response = xmlrpc_decode($response->value());
			
			$file = fopen('templates/roles.txt', "w");
			foreach($response as $role){
				$string = $role['id'] . ";" . substr(urldecode($role['name']),0,64) . "\n";
				fputs($file,$string);
			}
			fclose($file);
			echo "!!Processed Roles Tree!!<br>\n";
			
		}elseif($response->faultCode()){ # bad response   
			echo "Role Tree: A faultcode has been returned";
		}else{	# server is busy
			echo "Role Tree: No Connection To Server";
		}
?>