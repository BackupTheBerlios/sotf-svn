<?php
require_once($config['xmlrpcdir'] . "/xmlrpc.inc");  
require_once($config['xmlrpcdir'] . "/xmlrpcs.inc");  
//require_once('C:/sotf/helpers/ezxml/ezxml.php');

class rpc_Utils {
  
  var $debug = false;

  // timeout in seconds
  var $timeout = 5;
  
  function call($url, $method, $params) {
    // xmlrpc encode parameters
    for($i=0;$i<count($params);$i++){
      if(get_class($params[$i]) != 'xmlrpcval') {
        $params[$i] = xmlrpc_encode($params[$i]);
      }
    }
    // send request
    $message = new xmlrpcmsg($method, $params);
    if($this->debug) {
      //  $this->display_xml($message->serialize());
      print("<PRE>".htmlentities($message->serialize())."</PRE>\n");
      //("XML-RPC message:\n $message->serialize()",0);
    }
    $addr = parse_url($url);
    $client = new xmlrpc_client($url, $addr['host'], $addr['port']);
    if($this->debug)
      $client->setDebug(1);
    debug("XML-RPC", "call to " . $url);
    $response = $client->send($message);
    if($this->debug)
      print("<PRE>".htmlentities($response->serialize())."</PRE>\n");
    // process response
    //debug("XML-RPC Response", $response->serialize());
    if (!$response) {
      addError("No response: probably host is unreachable");
    } elseif ($response->faultCode() > 0) {
      // there was an error
      addError("Error response: " . $response->faultCode() . "  " . $response->faultString());
    } else {
      $retval = $response->value();
      if($retval)
        $retval = xmlrpc_decode($retval);
      //debug("Response", $retval);
      return $retval;
    }
    return NULL;
  }
  
  function callTamburine($method, $params) {
    global $config;

    // fetch config
    $urlParts = parse_url($config['tamburineURL']);
    $port= $urlParts['port'];
    $host = $urlParts['host'];
    $path = $urlParts['path'];
    debug("host", $host);
    debug("port", $port);
    debug("path", $path);
    

    $rawMessage = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<methodCall>
<methodName>setpls</methodName>
<params>
<param><value><string>/home/micsik/ok.mp3
</string></value></param>
<param><value><string>/home/micsik/china.mp3
</string></value></param>
</params>
</methodCall>";

    //prepare request header
    $rawRequest = "POST $path HTTP/1.1
Accept: */*
Accept-Encoding: deflate
TE: trailers,deflate
Host: $host:$port
User-Agent: Tamburine/0.2 libwww/5.4.0
Connection: TE,Keep-Alive
Date: Tue, 17 Jun 2003 08:59:10 GMT
Content-Length: " . strlen($rawMessage) . "
Content-Type: text/xml

$rawMessage";

    //$rawRequest = str_replace("\n", "\r\n", $rawRequest);
    /*
    // prepare data to send
    for($i=0;$i<count($params);$i++){
      if(get_class($params[$i]) != 'xmlrpcval') {
        $params[$i] = xmlrpc_encode($params[$i]);
      }
    }
    */

	 $fp=fsockopen($host, $port, $errno, $errstr, $this->timeout);
	 if (!$fp) {
     raiseError("Streaming error: $errstr ($errno)");
   }
   if(!socket_set_timeout($fp, $this->timeout)) 
     logError("could not set coket timeout");
	 if (!fputs($fp, $rawRequest, strlen($rawRequest))) {
		  raiseError('Streaming error: Write error');
   }
   while (!feof($fp)) {
     $rep = fgets ($fp,128);
     if(!$rep)
       raiseError('Streaming error: Read error');
     $rawReply .= $rep;
   }
   fclose ($fp);
   return $rawReply;
 }


/*
function display_xml_object($object)
{
	if ($object->type == TYPE_ELEMENT)
	{
		echo '<ul>&lt;<font color="blue">'.$object->name.'</font>';
		if ($object->attributes)
		{
			foreach ($object->attributes as $attribute)
			{
				$this->display_xml_object($attribute);
			}
		}
		echo "&gt;";
		if ($object->children)
		{
			foreach ($object->children as $child)
			{
				$this->display_xml_object($child);
			}
		}
		echo '&lt;/<font color="blue">'.$object->name.'</font>&gt;</ul>';
	}
	elseif ($object->type == TYPE_ATTRIBUTE)
	{
		echo ' <font color="red">'.$object->name.'</font>="';
		if ($object->children)
		{
			foreach ($object->children as $child)
			{
				$this->display_xml_object($child);
			}
		}
		echo '"';
	}
	elseif ($object->type == TYPE_TEXT)
	{
		echo $object->content;
	}
}

function display_xml_tree($tree)
{
	echo '&lt;?<font color="blue">xml</font> <font color="red">version</font>="' . $tree->version . '"?&gt;';
	
	if ($tree->children)
	{
		foreach($tree->children as $object)
		{
			$this->display_xml_object($object);
		}
	}
}

function display_xml($document)
{
	$tree = eZXML::domTree($document);
	echo '<tt>';
	$this->display_xml_tree($tree);
	echo '</tt>';
}
*/

}

?>