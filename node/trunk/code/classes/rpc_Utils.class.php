<?php
require_once("$xmlrpcdir/xmlrpc.inc");  
require_once("$xmlrpcdir/xmlrpcs.inc");  

class rpc_Utils {

function call($url, $method, $params) {
  // xmlrpc encode parameters
  for($i=0;$i<count($params);$i++){
    if(get_class($params[$i]) != 'xmlrpcval') {
      $params[$i] = xmlrpc_encode($params[$i]);
    }
  }
  // send request
  $message = new xmlrpcmsg($method, $params);
  debug("XML-RPC message", $message->serialize());
  $addr = parse_url($url);
  $client = new xmlrpc_client($url, $addr['host'], $addr['port']);
  //if($debug)
  //  $client->setDebug(1);
  debug("XML-RPC", "call to " . $url);
  $response = $client->send($message);
  
  // process response
  debug("XML-RPC Response", $response->serialize());
  if (!$response) {
    addError("No response","probably host is unreachable");
  } elseif ($response->faultCode() != 0) {
    // there was an error
    addError("Error response: ", $response->faultCode() . "  " . $response->faultString());
  } else {
    $retval = $response->value();
    if($retval)
      $retval = xmlrpc_decode($retval);
    debug("Response", $retval);
    return $retval;
  }
  return NULL;
}

}

?>