<?php
require_once($config['xmlrpcdir'] . "/xmlrpc.inc");  
require_once($config['xmlrpcdir'] . "/xmlrpcs.inc");  
//require_once('C:/sotf/helpers/ezxml/ezxml.php');

class rpc_Utils {
  
  var $debug = false;

  // timeout in seconds for establishing connection
  var $timeout = 20;
  
  function call($url, $method, $params) {
    // xmlrpc encode parameters
    for($i=0;$i<count($params);$i++){
      if(get_class($params[$i]) != 'xmlrpcval') {
        $params[$i] = xmlrpc_encoder($params[$i]);
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
    $response = $client->send($message, $this->timeout);
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
        $retval = xmlrpc_decoder($retval);
      //debug("Response", $retval);
      return $retval;
    }
    return NULL;
  }
  
  function callTamburine($method, $params) {
    global $config;

    if(!is_array($params)) {
      $params = array($params);
    }

    // fetch config
    $urlParts = parse_url($config['tamburineURL']);
    $port= $urlParts['port'];
    $host = $urlParts['host'];
    $path = $urlParts['path'];
    //debug("host", $host);
    //debug("port", $port);
    //debug("path", $path);

    /*
    // xmlrpc encode parameters
    for($i=0;$i<count($params);$i++){
      if(get_class($params[$i]) != 'xmlrpcval') {
        $xmlparams[$i] = xmlrpc_encoder($params[$i]);
      }
    }
    $msg = new xmlrpcmsg($method, $xmlparams);
    // this does not work because it rawurlencodes string values
    // and Tamburine does not understand it
    $rawMessage = $msg->serialize();
    */

    $rawMessage = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<methodCall>\n<methodName>$method</methodName>\n<params>";
    for($i=0;$i<count($params);$i++){
      $rawMessage .= "\n<param><value>";
      $value = trim($params[$i]);
      if(is_numeric($value)) {
        $rawMessage .= "<i4>$value</i4>";
      } else {
        $rawMessage .= "<string>$value</string>";
      }
      $rawMessage .= "</value></param>";
    }
    $rawMessage .= "\n</params>\n</methodCall>";
    
    //prepare request header
    $header = "POST $path HTTP/1.1
Accept: */*
Accept-Encoding: deflate
TE: trailers,deflate
Host: $host:$port
User-Agent: Tamburine/0.2 libwww/5.4.0
Connection: TE,Keep-Alive
Date: Tue, 17 Jun 2003 08:59:10 GMT
Content-Length: " . strlen($rawMessage) . "
Content-Type: text/xml

";

	 $fp=fsockopen($host, $port, $errno, $errstr, $this->timeout);
	 if (!$fp) {
     raiseError("Streaming error: $errstr ($errno)");
   }
   if(!socket_set_timeout($fp, $this->timeout)) 
     logError("could not set coket timeout");
	 if (!fputs($fp, $header, strlen($header))) {
		  raiseError('Streaming error: Write error');
   }
   fflush($fp);
   debug("FLUSH", 1);
	 if (!fputs($fp, $rawMessage, strlen($rawMessage))) {
		  raiseError('Streaming error: Write error');
   }
   //fflush($fp);
   debug("SENT ALL", 1);
   while (!feof($fp)) {
     $rep = fread ($fp, 1024);
     if($rep === FALSE)
       raiseError('Streaming error: Read error');
     $rawReply .= $rep;
   }
   fclose ($fp);

   $parts = explode("\n\n", $rawReply);
   if(count($parts) > 2)
     raiseError("could not parse response");
   $header = $parts[0];
   $content = $parts[1];
   $content = preg_replace('/<\?.*\?>/','', $content);

   $msg = new xmlrpcmsg('foo', '');
   $resp = $msg->parseResponse($content);
   //dump($resp->value(), "RETVAL1");
   $retval = xmlrpc_decoder($resp->value());
   return $retval;
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