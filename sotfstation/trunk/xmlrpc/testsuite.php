<?php					// -*-c++-*-
include("xmlrpc.inc");
require "phpunit.php";

$DEBUG=0;
$LOCALSERVER="xmlrpc.heddley.com";
$HTTPSSERVER="xmlrpc.usefulinc.com";
$suite = new TestSuite;

class TestLocalhost extends TestCase {
	function TestLocalhost($name) {
		$this->TestCase($name);
	}

	function setUp() {
		global $DEBUG, $LOCALSERVER;
		$this->client=new xmlrpc_client("/server.php", 
																		$LOCALSERVER, 80);
		if ($DEBUG) $this->client->setDebug(1);
	}

	function stringTest() {
		$sendstring="here are some \"entities\" < > & and " .
			"here's a dollar sign \$pretendvarname and a backslash too " . chr(92) . 
			" - isn't that great? \\\"hackery\\\" at it's best " .
			" also don't want to miss out on \$item[0]";
		  $f=new xmlrpcmsg('examples.stringecho', 
											 array(new xmlrpcval($sendstring, "string")));
			$r=$this->client->send($f);
			$v=$r->value();
			$this->assertEquals($sendstring, $v->scalarval());
	}

	function addingDoublesTest() {
		// note that rounding errors mean i
		// keep precision to sensible levels here ;-)
		$a=12.13; $b=-23.98;
		$f=new xmlrpcmsg('examples.addtwodouble',
										 array(new xmlrpcval($a, "double"),
													 new xmlrpcval($b, "double")));
		$r=$this->client->send($f);
		$v=$r->value();
		$this->assertEquals($a+$b,$v->scalarval());
	}

  function addingTest() {
    $f=new xmlrpcmsg('examples.addtwo',
		     array(new xmlrpcval(12, "int"),
			   new xmlrpcval(-23, "int")));
    $r=$this->client->send($f);
    $v=$r->value();
    $this->assertEquals(12-23, $v->scalarval());
  }
  
  function invalidNumber() {
		$f=new xmlrpcmsg('examples.addtwo',
										 array(new xmlrpcval("fred", "int"),
													 new xmlrpcval("\"; exec('ls')", "int")));
		$r=$this->client->send($f);
		$v=$r->value();
		// TODO: a fault condition should be generated here
		// by the server, which we pick up on
		$this->assertEquals(0, $v->scalarval());
	}

	function booleanTest() {
		 $f=new xmlrpcmsg('examples.invertBooleans', 
								 array(new xmlrpcval(
																		 array(new xmlrpcval(true, "boolean"),
																					 new xmlrpcval(false, "boolean"),
																					 new xmlrpcval(1, "boolean"),
																					 new xmlrpcval(0, "boolean"),
																					 new xmlrpcval("true", "boolean"),
																					 new xmlrpcval("false", "boolean")), 
																		 "array")));
		 $answer="010101";
		 $r=$this->client->send($f);
		 $this->assert(!$r->faultCode());
		 $v=$r->value();
		 $sz=$v->arraysize();
		 $got="";
		 for($i=0; $i<$sz; $i++) {
			 $b=$v->arraymem($i);
			 if ($b->scalarval())	$got.="1";
			 else $got.="0";
		 }
		 $this->assertEquals($answer, $got);
	}

	function base64Test() {
		$sendstring="Mary had a little lamb,
Whose fleece was white as snow,
And everywhere that Mary went
the lamb was sure to go.

Mary had a little lamb
She tied it to a pylon
Ten thousand volts went down its back
And turned it into nylon";
		 $f=new xmlrpcmsg('examples.decode64',
											array(new xmlrpcval($sendstring, "base64")));
		 $r=$this->client->send($f);
		 $v=$r->value();
		 $this->assertEquals($sendstring, $v->scalarval());
	}


}

class TestFileCases extends TestCase {
	function TestFileCases($name, $base="/var/www/xmlrpc") {
		$this->TestCase($name);
		$this->root=$base;
	}

	function stringBug () {
		$m=new xmlrpcmsg("dummy");
		$fp=fopen($this->root."/bug_string.xml", "r");
		$r=$m->parseResponseFile($fp);
		$v=$r->value();
		fclose($fp);
		$s=$v->structmem("sessionID");
		$this->assertEquals("S300510007I", $s->scalarval());
	}
	
	function whiteSpace () {
		$m=new xmlrpcmsg("dummy");
		$fp=fopen($this->root."/bug_whitespace.xml", "r");
		$r=$m->parseResponseFile($fp);
		$v=$r->value();
		fclose($fp);
		$s=$v->structmem("content");
		$this->assertEquals("hello world. 2 newlines follow\n\n\nand there they were.", $s->scalarval());
	}
}

class TestInvalidHost extends TestCase {
  function TestInvalidHost($name) {
    $this->TestCase($name);
  }
  
  function setUp() {
    global $DEBUG,$LOCALSERVER;
    $this->client=new xmlrpc_client("/NOTEXIST.php", 
				    $LOCALSERVER, 80);
    if ($DEBUG) $this->client->setDebug(1);
  }
  
  function test404() {
    $f=new xmlrpcmsg('examples.echo',
		     array(new xmlrpcval("hello", "string")));
    $r=$this->client->send($f);
    $this->assertEquals(5, $r->faultCode());
  }
}

class TestHTTPSConnection extends TestCase {
  function TestInvalidHost($name) {
    $this->TestCase($name);
  }
  
  function setUp() {
    global $DEBUG,$HTTPSSERVER;
    $this->client=new xmlrpc_client("/demo/server.php", 
				    $HTTPSSERVER);
    //$this->client->setCertificate("/var/www/xmlrpc/rsakey.pem",
    //			  "test");
    if ($DEBUG || 1) $this->client->setDebug(1);
  }

  function addingTest() {
    $f=new xmlrpcmsg('examples.getStateName',
		     array(new xmlrpcval(23, "int")));
    $r=$this->client->send($f, 180, "https");
    if ($r->faultCode()) {
      // create dummy value so assert fails
      $v=new xmlrpcval("SSL send failed.");
      print "<pre>Fault: " . $r->faultString() . "</pre>";
    } else {
      $v=$r->value();
    }
    $this->assertEquals("Michigan", $v->scalarval());
  }
}

$suite->addTest(new TestLocalhost("stringTest"));
$suite->addTest(new TestLocalhost("addingTest"));
$suite->addTest(new TestLocalhost("addingDoublesTest"));
$suite->addTest(new TestLocalhost("invalidNumber"));
$suite->addTest(new TestLocalhost("booleanTest"));
$suite->addTest(new TestLocalhost("base64Test"));
$suite->addTest(new TestInvalidHost("test404"));
$suite->addTest(new TestFileCases("stringBug"));
$suite->addTest(new TestFileCases("whiteSpace"));
$suite->addTest(new TestHTTPSConnection("addingTest"));
$title = 'XML-RPC Unit Tests';
?>
<html>
<head><title><?php echo $title; ?></title></head>
<body>
<h1><?php echo $title; ?></h1>
<p>Note, tests beginning with 'f_' <i>should</i> fail.</p>
<p>
<?php
if (isset($only))
     $suite = new TestSuite($only);

$testRunner = new TestRunner;
$testRunner->run($suite);
?>
</body>
</html>
