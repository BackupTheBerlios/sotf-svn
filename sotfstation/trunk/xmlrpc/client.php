<html>
<head><title>xmlrpc</title></head>
<body>
<?php
include("xmlrpc.inc");

if ($HTTP_POST_VARS["stateno"]!="") {
  $f=new xmlrpcmsg('examples.getStateName',
				   array(new xmlrpcval($HTTP_POST_VARS["stateno"], "int")));
  print "<pre>" . htmlentities($f->serialize()) . "</pre>\n";
  $c=new xmlrpc_client("/server.php", "xmlrpc.heddley.com", 80);
  $c->setDebug(1);
  $r=$c->send($f);
  if (!$r) { die("send failed"); }
  $v=$r->value();
  if (!$r->faultCode()) {
	print "State number ". $HTTP_POST_VARS["stateno"] . " is " .
	  $v->scalarval() . "<BR>";
	// print "<HR>I got this value back<BR><PRE>" .
	//  htmlentities($r->serialize()). "</PRE><HR>\n";
  } else {
	print "Fault: ";
	print "Code: " . $r->faultCode() . 
	  " Reason '" .$r->faultString()."'<BR>";
  }
}
print "<FORM ACTION=\"client.php\" METHOD=\"POST\">
<INPUT NAME=\"stateno\" VALUE=\"${stateno}\"><input type=\"submit\" value=\"go\" name=\"submit\"></FORM><P>
enter a state number to query its name";

?>
</body>
</html>
