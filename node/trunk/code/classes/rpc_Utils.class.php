<?php
require_once("$xmlrpcdir/xmlrpc.inc");  
require_once("$xmlrpcdir/xmlrpcs.inc");  
//require_once('C:/sotf/helpers/ezxml/ezxml.php');

class rpc_Utils {

var $debug = false;

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