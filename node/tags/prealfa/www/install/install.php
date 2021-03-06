<?php
ini_set("max_execution_time", "90");
header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
//header ("Expires: Mon, 26 Jul 2010 05:00:00 GMT");
//TODO:debug infok kikapcsolasa!!!
// TODO: cache dir letrehozasa!!
// TODO chekc imagemagick installed

function PrintTitle($number)		//'header' af all tests
{
	set_time_limit(30);		//extends the time limit for the next 30 seconds (every test has so max. 30 seconds to run)
	global $install_test_name, $install_color;
	print('<TR><TD BGCOLOR="'.$install_color[$number].'">');		//begin new table row, color is set here
	print('<DIV ALIGN="center"><B>'.$install_test_name[$number].'<BR /><BR /></B></DIV>');		//prints the name of the test
}

function PrintButton($number)		//'footer' of all tests
{
	global $install_test_result, $install_color;
	print('<BR /><DIV ALIGN="center">'.$install_test_result[$number].'</DIV>');		//prints the result string of the tests
	print('<INPUT type="hidden" name="test_result[]" value="'.htmlentities($install_test_result[$number]).'">');	//stores the result string in a hidden field (no need to rerun the test)
	print('<INPUT type="hidden" name="color[]" value="'.$install_color[$number].'">');		//stores the color value	-||-
	print('<BR /><DIV ALIGN="center"><INPUT type="submit" name="run_test" value="Run test '.$number.'"></DIV>');	//prints a run test button user can run this test again
	print('</TD></TR>');		//end new table row
	flush();			//writes out the row (if no buffer the user can see it)
}

function RunTest($number, $testname, $required = -1)		//returns if or not to run the test again
{
	global $install_run_test, $install_test_result, $install_test_name, $install_green, $install_color;
	$install_test_name[$number] = $testname;
	if ($required == -1)		//if no test is required for this one
		return (($install_run_test == "Run test ".$number) OR ($install_test_result[$number] == NULL));		//if button pressed or no results (not run yet)
	else				//if a pervious test needs to be OK
		return ( ($install_run_test == "Run test ".$number) OR (($install_test_result[$number] == NULL) AND ($install_color[$required] == $install_green)) );	//same but the other test must be OK

}

function GetPerm($filename, $option)		//tries to write or read files and directories
{
	if ($option == "read")			////read a file
	{
		if (file_exists($filename))
		{
			if (!$fp = fopen($filename, 'r'))
			{
				return("read ERROR");		//file exists but can not be read
			}
			fclose($fp);
			return("OK readable");			//file is readable
		}
		else		//file does not exists
		{
		return("ERROR file does not exists");
		}
	}
	elseif ($option == "write")		////write a non existing file (directory write test)
	{
		if (file_exists($filename)) return("ERROR file in use");	//file exists ERROR
		if (!$fp = fopen($filename, 'a'))			//create file
		{
			return("creating ERROR");			//file can not be created
		}
		if (!fwrite($fp, "alma"))
		{
			return("write ERROR");				//file can not be written
		}
		fclose($fp);
		unlink($filename);					//delete file
		return("OK writeable");					//file is writeable
	}
	elseif ($option == "dir")			////directory read test
	{
		if (file_exists($filename))
		{
			if (!$dir = @opendir($filename))	//read files in dir
			{
				return("read ERROR");
			}
			closedir($dir);				//close dir handler
			return("OK readable");
		}
		else
		{
		return("ERROR directory does not exists");
		}
	}
	elseif ($option == "append")			////write to an existing or not existing file
	{
		if (!$fp = fopen($filename, 'a'))	//open file
		{
			return("creating ERROR");	//could not be opened
		}
		if (!fwrite($fp, "\ninstall scipt try\n"))	//write string
		{
			return("write ERROR");			//write error
		}
		fclose($fp);					//clode file
		return("OK writeable");
	}
	else return "ERROR ".$option." unknown in GetPerm";	////bad option parameter
}

function addParent($name, $topic_name = "", $lang = "en")
{
	$x = new sotf_NodeObject("sotf_topic_tree_defs");
	$x->set('supertopic', 0);
	$x->set('name', $name);
	$x->create();
	$id = $x->getID();
	if ($topic_name != "")
	{
		$y = new sotf_NodeObject("sotf_topics");
		$y->set('topic_id', $id);
		$y->set('language', $lang);
		$y->set('topic_name', $topic_name);
		$y->create();
		//print($id);
	}
	return $id;
}

function addChild($parent, $name, $topic_name = "", $lang = "en")
{
	$x = new sotf_NodeObject("sotf_topic_tree_defs");
	$x->set('supertopic', $parent);
	$x->set('name', $name);
	$x->create();
	$id = $x->getID();
	if ($topic_name != "")
	{
		$y = new sotf_NodeObject("sotf_topics");
		$y->set('topic_id', $id);
		$y->set('language', $lang);
		$y->set('topic_name', $topic_name);
		$y->create();
		//print($id);
	}
	return $x->getID();
}


/*
Initial parameters
*/
$install_maxtests = 6;					//number of tests
$install_red  =  "FF5555";				//red for ERROR
$install_green = "00FF00";				//green for OK
$install_blue =  "0000FF";				//blue for not tested
$install_test_name = "";				//array for the name of the tests


$install_user = $HTTP_POST_VARS["user"];		//username for DB
$install_pass = $HTTP_POST_VARS["pass"];		//password for DB
$install_host = $HTTP_POST_VARS["host"];		//host for DB
$install_port = $HTTP_POST_VARS["port"];		//port for DB

$install_sadm_user = $HTTP_POST_VARS["sadm_user"];		//username for DB
$install_sadm_pass = $HTTP_POST_VARS["sadm_pass"];		//password for DB
$install_sadm_host = $HTTP_POST_VARS["sadm_host"];		//host for DB
$install_sadm_port = $HTTP_POST_VARS["sadm_port"];		//port for DB
$install_sadm_db_name = $HTTP_POST_VARS["sadm_db_name"];	//DB name

$install_node_user = $HTTP_POST_VARS["node_user"];		//username for DB
$install_node_pass = $HTTP_POST_VARS["node_pass"];		//password for DB
$install_node_host = $HTTP_POST_VARS["node_host"];		//host for DB
$install_node_port = $HTTP_POST_VARS["node_port"];		//port for DB
$install_node_db_name = $HTTP_POST_VARS["node_db_name"];	//DB name

$install_run_test = $HTTP_POST_VARS["run_test"];	//Run test X buttons
$install_run_all = $HTTP_POST_VARS["RUN_ALL"];		//Run all button
$install_reload = $HTTP_POST_VARS["reload"];		//Reload config.inc.php button
$install_createdb = $HTTP_POST_VARS["createdb"];	//Create sadm db button
$install_writeback_sadm = $HTTP_POST_VARS["writeback_sadm"];	//write sadm connection params to config.inc.php
$install_writeback_node = $HTTP_POST_VARS["writeback_node"];	//write node connection params to config.inc.php

$install_delete_topic = $HTTP_POST_VARS["delete_topic"];	//delete topic tree
$install_create_topic = $HTTP_POST_VARS["create_topic"];	//create topic tree


$install_color = $HTTP_POST_VARS["color"];		//color values for the cells
$install_test_result = $HTTP_POST_VARS["test_result"];	//result strings of the tests


if ($install_user === NULL)		//set default parameter if first time here
{
	$install_user = 'username';
	$install_pass = 'password';
	$install_host = 'host';
	$install_port = '5432';

	for ($i=0; $i <= $install_maxtests; $i++)
	{
		$install_color[$i]=$install_blue;	//set blue color as default
		$install_test_result[$i] = NULL;	//no test results (forces tests to run)
	}
}

if ($install_run_all != NULL)		//if run_all button pressed
{
	for ($i=0; $i <= $install_maxtests; $i++)
	{
		$install_color[$i]=$install_blue;	//set blue color as default
		$install_test_result[$i] = NULL;	//no test results (forces tests to run)
	}
}


if (($install_color[$id] = $install_green) AND ($nodeDbHost == NULL))			//if test 2 passed and not already included
	@include("../config.inc.php");

//@include "install_tests.php";

?>

<HTML>
<HEAD>
<TITLE>Install</TITLE>
</HEAD>
<BODY>
<FORM method="post" action="install.php">
<DIV ALIGN="center"><H2>Install</H2></DIV>

<INPUT type="hidden" name="test_result[]" value="<?php print($install_test_result[0]) ?>">
<INPUT type="hidden" name="color[]" value="FFFFFF">
<DIV ALIGN="center">
<TABLE BORDER=1 CELLPADDING=5 CELLSPACING=0 WIDTH="60%" BGCOLOR="<?php print($install_color[0]) ?>">

<?php
	$id = 1;	//////////////////////////Test 1
	if (RunTest($id, "Server configuration"))
	{
		if ($_SERVER["PHP_SELF"] == NULL)
		{
			$install_test_result[$id] = "PHPSELF can not be read.";
			$install_color[$id] = $install_red;
		}
		else
		{
			$install_test_result[$id] = "Current location: ".$_SERVER["PHP_SELF"];
			$install_test_result[$id] .= "<BR />Server software: ".$_SERVER["SERVER_SOFTWARE"];
			$install_test_result[$id] .= "<BR />Server protocol: ".$_SERVER["SERVER_PROTOCOL"];
			$install_test_result[$id] .= "<BR />Remote address: ".$_SERVER["REMOTE_ADDR"];
			$install_test_result[$id] .= "<BR />Extensions loaded: ".implode(", ", array_values(get_loaded_extensions()));

			$install_test_result[$id] .= "<BR />Web server - PHP interface: ".php_sapi_name();
			$install_test_result[$id] .= "<BR />PHP version: ".phpversion();

//			print_r();
//			print(php_sapi_name());

			$install_color[$id] = $install_green;
		}
	}
	for ($i=0;$i<256;$i++) echo " ";

	PrintTitle($id);
	PrintButton($id);


	$id = 2;	//////////////////////////Test 2
	if (RunTest($id, "'config.inc.php' file include test") or ($install_reload != NULL))
	{
		$error = false;
		$install_test_result[$id] = "config.inc.php: ";
	
		if (!file_exists("../config.inc.php"))
		{
			$install_test_result[$id] .= "file not exists.";
			$error = true;
		}
		elseif (GetPerm("../config.inc.php", "read") != "OK readable")
		{
			$install_test_result[$id] .= "reading error";
			$error = true;
		}
	
		if ($error)
		{
			$install_color[$id] = $install_red;
		}
		else
		{
			@include("../config.inc.php");
			if ($nodeDbHost != NULL)		//in iclude successfull
			{
					//set default parameter if first time successfull or reload button pressed
				if (($install_color[$id] != $install_green) or ($install_reload != NULL))
				{
					$install_user = $userDbUser;			//username for DB
					$install_pass = $userDbPasswd;			//password for DB
					$install_host = $userDbHost;			//host for DB
					$install_port = $userDbPort;			//port for DB
					
					
					$install_sadm_user = $userDbUser;		//username for DB
					$install_sadm_pass = $userDbPasswd;		//password for DB
					$install_sadm_host = $userDbHost;		//host for DB
					$install_sadm_port = $userDbPort;		//port for DB
					$install_sadm_db_name = $userDbName;		//DB name
					
					
					$install_node_user = $nodeDbUser;		//username for DB
					$install_node_pass = $nodeDbPasswd;		//password for DB
					$install_node_host = $nodeDbHost;		//host for DB
					$install_node_port = $nodeDbPort;		//port for DB
					$install_node_db_name = $nodeDbName;		//DB name
				}
				$install_color[$id] = $install_green;
				$install_test_result[$id] .= "OK";
			}
			else					//include error
			{
				$install_color[$id] = $install_red;
				$install_test_result[$id] .= "include error";
			}
		}
	}
	PrintTitle($id);
	print('<DIV ALIGN="center"><BR /><INPUT type="submit" name="reload" value="Reload config.inc.php"></DIV>');
	PrintButton($id);


	$id = 3;	//////////////////////////Test 3
	if (RunTest($id, "Directory and file permissions", 2))
	{
		$install_test_result[$id] = "";

		//log file
		$install_test_result[$id] .= "logFile ($logFile) ".@GetPerm($logFile, "append")."<BR />";

		//directories with write permission
		$install_test_result[$id] .= "repositoryDir ($repositoryDir) ".@GetPerm($repositoryDir."/pmppmp.pmp", "write")."<BR />";
		$install_test_result[$id] .= "userDirs ($userDirs) ".@GetPerm($userDirs."/pmppmp.pmp", "write")."<BR />";

		$install_test_result[$id] .= "logs ($basedir/code/logs) ".@GetPerm($basedir."/code/logs/pmppmp.pmp", "write")."<BR />";
		$install_test_result[$id] .= "templates_c ($basedir/code/templates_c) ".@GetPerm($basedir."/code/templates_c/pmppmp.pmp", "write")."<BR />";
		$install_test_result[$id] .= "tmp (../tmp) ".@GetPerm("../tmp/pmppmp.pmp", "write")."<BR />";
//		$install_test_result[$id] .= " ($basedir) ".@GetPerm($basedir."/pmppmp.pmp", "write")."<BR />";

		//other directories
		$install_test_result[$id] .= "basedir ($basedir) ".@GetPerm($basedir, "dir")."<BR />";
		$install_test_result[$id] .= "getid3dir ($getid3dir) ".@GetPerm($getid3dir, "dir")."<BR />";
		$install_test_result[$id] .= "musicDir ($musicDir) ".@GetPerm($musicDir, "dir")."<BR />";

		$install_test_result[$id] .= "classes ($basedir/code) ".@GetPerm($basedir."/code", "dir")."<BR />";
		$install_test_result[$id] .= "classes ($basedir/code/classes) ".@GetPerm($basedir."/code/classes", "dir")."<BR />";

		//files that are required by init.inc.php
		$install_test_result[$id] .= "peardir ($peardir/DB.php) ".@GetPerm($peardir."/DB.php", "read")."<BR />";					//require($peardir . '/DB.php');
		$install_test_result[$id] .= "smartydir ($smartydir/Smarty.class.php) ".@GetPerm($smartydir."/Smarty.class.php", "read")."<BR />";		//require($smartydir . '/Smarty.class.php');
		$install_test_result[$id] .= "smartydir ($smartydir/Config_File.class.php) ".@GetPerm($smartydir."/Config_File.class.php", "read")."<BR />";	//require($smartydir . '/Config_File.class.php');

		$install_test_result[$id] .= "classdir ($classdir/db_Wrap.class.php) ".@GetPerm($classdir."/db_Wrap.class.php", "read")."<BR />";		//require($classdir . '/db_Wrap.class.php');
		$install_test_result[$id] .= "classdir ($classdir/sotf_Utils.class.php) ".@GetPerm($classdir."/sotf_Utils.class.php", "read")."<BR />";		//require($classdir . '/sotf_Utils.class.php');
		$install_test_result[$id] .= "classdir ($classdir/sotf_User.class.php) ".@GetPerm($classdir."/sotf_User.class.php", "read")."<BR />";		//require($classdir . '/sotf_User.class.php');
		$install_test_result[$id] .= "classdir ($classdir/sotf_Page.class.php) ".@GetPerm($classdir."/sotf_Page.class.php", "read")."<BR />";		//require($classdir . '/sotf_Page.class.php');
		$install_test_result[$id] .= "classdir ($classdir/sotf_Permission.class.php) ".@GetPerm($classdir."/sotf_Permission.class.php", "read")."<BR />";	//require($classdir . '/sotf_Permission.class.php');
		$install_test_result[$id] .= "classdir ($classdir/sotf_Vars.class.php) ".@GetPerm($classdir."/sotf_Vars.class.php", "read")."<BR />";		//require($classdir . '/sotf_Vars.class.php');
		$install_test_result[$id] .= "classdir ($classdir/sotf_Repository.class.php) ".@GetPerm($classdir."/sotf_Repository.class.php", "read")."<BR />";	//require($classdir . '/sotf_Repository.class.php');
		$install_test_result[$id] .= "classdir ($classdir/sotf_FileList.class.php) ".@GetPerm($classdir."/sotf_FileList.class.php", "read")."<BR />";	//require($classdir . '/sotf_FileList.class.php');
		$install_test_result[$id] .= "classdir ($classdir/sotf_AudioCheck.class.php) ".@GetPerm($classdir."/sotf_AudioCheck.class.php", "read")."<BR />";	//require($classdir . '/sotf_AudioCheck.class.php'); 

//		$install_test_result[$id] .= "rosszdir (C:/temp/temp) ".@GetPerm("C:/temp/temp/pmppmp.pmp", "read")."<BR />";
//		$install_test_result[$id] .= " () ".GetPerm(."/pmppmp.pmp")."<BR />";
	
		if (strpos($install_test_result[$id], "ERROR"))
		{
			$install_color[$id] = $install_red;
		}
		else
		{
			$install_color[$id] = $install_green;
		}
	}
	PrintTitle($id);
	PrintButton($id);


	$id = 4;	//////////////////////////Test 4
	if (RunTest($id, "PostGresql connection"))
	{
		$conn = @pg_connect("host=$install_host port=$install_port user=$install_user dbname=template1 password=$install_pass");
		if (!$conn)
		{
			$install_test_result[$id] = "Connecting to PostGreSQL failed";
			$install_color[$id] = $install_red;
		}
		else
		{
			$install_test_result[$id] = "OK";
			$install_color[$id] = $install_green;
		}
		@pg_close($conn);
	}
	PrintTitle($id);
	print('
	<DIV ALIGN="center">
	Username: <INPUT type="text" name="user" value="'.$install_user.'"><BR />
	Password: <INPUT type="password" name="pass" value="'.$install_pass.'"><BR />
	Hostname: <INPUT type="text" name="host" value="'.$install_host.'"> Port: <INPUT type="text" name="port" value="'.$install_port.'" SIZE=5><BR />
	</DIV>');
	PrintButton($id);


	$id = 5;	//////////////////////////Test 5
	if (RunTest($id, "DB connection to 'sadm'", 4))		//////////////////////////Test 4 should be OK to run this test
	{
		$conn = @pg_connect("host=$install_sadm_host port=$install_sadm_port dbname=$install_sadm_db_name user=$install_sadm_user password=$install_sadm_pass");
		if (!$conn)
		{
			$conn = @pg_connect("host=$install_sadm_host port=$install_sadm_port dbname=template1 user=$install_sadm_user password=$install_sadm_pass");
			if (!$conn)
			{
				$install_test_result[$id] = "Could not connect.";
				$install_color[$id] = $install_red;
			}
			else
			{
				$install_test_result[$id] = "Database 'sadm' not found, please install SADM.";
				$install_color[$id] = $install_red;
			}
		}
		else
		{
			$sql = "select count(*) from authenticate where username = 'admin'";
			$result = @pg_query($conn, $sql);
			$a = @pg_fetch_row($result, 0);
			if ( $a[0] != 1)
			{
				$install_test_result[$id] = "Admin user in table authenticate not found.";
				$install_color[$id] = $install_red;
			}
			else
			{
				$install_test_result[$id] = "OK";
				$install_color[$id] = $install_green;
			}
		}
		@pg_close($conn);
	}
//$install_writeback_sadm
	PrintTitle($id);
	print('
	Username: <INPUT type="text" name="sadm_user" value="'.$install_sadm_user.'"><BR />
	Password: <INPUT type="password" name="sadm_pass" value="'.$install_sadm_pass.'"><BR />
	Hostname: <INPUT type="text" name="sadm_host" value="'.$install_sadm_host.'"> Port: <INPUT type="text" name="sadm_port" value="'.$install_sadm_port.'" SIZE=5><BR />
	Database name: <INPUT type="text" name="sadm_db_name" value="'.$install_sadm_db_name.'"><BR />');
	if ( ($install_color[$id] == $install_green) AND ( ($install_sadm_user != $userDbUser) OR ($install_sadm_pass != $userDbPasswd)
		OR ($install_sadm_host != $userDbHost) OR ($install_sadm_port != $userDbPort) OR ($install_sadm_db_name != $userDbName) ) )
			print('<DIV ALIGN="center"><BR /><INPUT type="submit" name="writeback_sadm" value="Write new values to config.inc.php" disabled=true></DIV>');
	PrintButton($id);
	
	$id = 6;	//////////////////////////Test 6
	if (RunTest($id, "DB connection to 'node'", 5))		//////////////////////////Test 5 should be OK to run this test
	{
		$conn = @pg_connect("host=$install_node_host port=$install_node_port dbname=$install_node_db_name user=$install_node_user password=$install_node_pass");
		if (!$conn)
		{
			$install_test_result[$id] = "Database '".$install_node_db_name."' not found";
			$install_color[$id] = $install_red;
		}
		else
		{
			$install_test_result[$id] = "OK";
			$install_color[$id] = $install_green;
		}
		@pg_close($conn);
	}
	if ($install_createdb)			//if create node db button pressed
	{
		$conn = @pg_connect("host=$install_host port=$install_port user=$install_user dbname=template1 password=$install_pass");	//connect
		if (!$conn)
		{
			$install_test_result[$id] = "Could not connect.";
			$install_color[$id] = $install_red;
		}
		else
		{
			$sql = "CREATE DATABASE ".$install_node_db_name;		//create new db
			$result = @pg_query($conn, $sql);
			if (!$result)
			{
				$install_test_result[$id] = "Could not create db.";
				$install_color[$id] = $install_red;
			}
			else
			{
				@pg_close($conn);		//close old connection
				$conn = @pg_connect("host=$install_host port=$install_port user=$install_user dbname=$install_node_db_name password=$install_pass");	//Connect to the new DB
				if (!$conn)
				{
					$install_test_result[$id] = "Could not connect to the new db.";
					$install_color[$id] = $install_red;
				}
				else
				{
					//Read SQL commands from db.sql and execute them
					$fd = @fopen ($basedir."/code/doc/db.sql", "r");
					if (!$fd)
					{
						$install_test_result[$id] = "Sql file ($basedir/code/doc/db.sql) not found.";
						$install_color[$id] = $install_red;
					}
					else
					{
						$buffer = "";
						while (!feof ($fd))			//read the whole file
						{
							$line = fgets($fd, 1024);	//read a line with max lengts 1024 byte
							$pos = strpos($line, "--");	//find comment position
							if ($pos === false) $buffer .= $line;	//if no in line add it to the buffer
							else $buffer .= substr($line, 0, $pos); 	//if any comment remove it first
						}
						fclose ($fd);

						$buffer = rtrim($buffer);				//delete spaces from end
						$sql = explode(";", $buffer);				//divide into single commands

						$max = count($sql);					//count commands
						$install_color[$id] = $install_green;
						$install_test_result[$id] = "";				//delete pervious results
						for($i=0; $i<$max; $i++) if ($sql[$i] != '')		//execute all commands if not empty
						{
							$result = @pg_query($conn, $sql[$i]);
							//$result = $db->query();
							if (!$result)
								{
								$install_test_result[$id] .= "<BR><DIV ALIGN='center'>-------".pg_last_error($conn)."-------<BR>".$sql[$i]."<BR></DIV>";
								$install_color[$id] = $install_red;
								}
						}
						if ($install_color[$id] == $install_green) $install_test_result[$id] = "OK";
					}
				}
			}
		}
	}
//$install_writeback_node
	PrintTitle($id);
	print('
	Username: <INPUT type="text" name="node_user" value="'.$install_node_user.'"><BR />
	Password: <INPUT type="password" name="node_pass" value="'.$install_node_pass.'"><BR />
	Hostname: <INPUT type="text" name="node_host" value="'.$install_node_host.'"> Port: <INPUT type="text" name="node_port" value="'.$install_node_port.'" SIZE=5><BR />
	Database name: <INPUT type="text" name="node_db_name" value="'.$install_node_db_name.'"><BR />');
	if ( ($install_color[$id] == $install_green) AND ( ($install_node_user != $nodeDbUser) OR ($install_node_pass != $nodeDbPasswd)
		OR ($install_node_host != $nodeDbHost) OR ($install_node_port != $nodeDbPort) OR ($install_node_db_name != $nodeDbName) ) )
				print('<DIV ALIGN="center"><BR /><INPUT type="submit" name="writeback_node" value="Write new values to config.inc.php" disabled=true></DIV>');
	if ($install_color[$id] == $install_red) print('<DIV ALIGN="center"><BR /><INPUT type="submit" name="createdb" value="Create NODE db"></DIV>');
	PrintButton($id);


	$id = 7;	//////////////////////////Test 7
	if (RunTest($id, "TopicTree", 6) OR isset($install_create_topic) OR isset($install_delete_topic))
	{
		if (isset($install_create_topic))
		{
			require("init.inc.php");

			$id = addParent("development", "Development");
			addChild($id, "agriculture", "Agriculture");
			addChild($id, "aid", "Aid");
			addChild($id, "capacity building", "Capacity Building");
			addChild($id, "children", "Children");
			addChild($id, "cities", "Cities");
			addChild($id, "education", "Education");
			addChild($id, "emergency relief", "Emergency Relief");
			addChild($id, "energy", "Energy");
			addChild($id, "fisheries", "Fisheries");
			addChild($id, "food", "Food");
			addChild($id, "gender", "Gender");
			addChild($id, "intermediate technology", "Intermediate Technology");
			addChild($id, "international cooperation", "International Cooperation");
			addChild($id, "labour/work", "Labour/Work");
			addChild($id, "land", "Land");
			addChild($id, "migration", "Migration");
			addChild($id, "population", "Population");
			addChild($id, "poverty", "Poverty");
			addChild($id, "refugees", "Refugees");
			addChild($id, "shelter/housing", "Shelter/Housing");
			addChild($id, "social exclusion", "Social Exclusion");
			addChild($id, "tourism", "Tourism");
			addChild($id, "transport", "Transport");
			addChild($id, "volunteering", "Volunteering");
			addChild($id, "water/sanitation", "Water/Sanitation");
			addChild($id, "youth", "Youth");
			addChild($id, "old age / retirement", "Old Age / Retirement");

			$id = addParent("economy", "Economy");
			addChild($id, "business", "Business");
			addChild($id, "consumption/consumerism", "Consumption/Consumerism");
			addChild($id, "corporations", "Corporations");
			addChild($id, "credit/investment", "Credit/Investment");
			addChild($id, "debt", "Debt");
			addChild($id, "finance", "Finance");
			addChild($id, "microcredit", "Microcredit");
			addChild($id, "social enterprise", "Social Enterprise");
			addChild($id, "trade", "Trade");

			$id = addParent("environment", "Environment");
			addChild($id, "animals", "Animals");
			addChild($id, "atmosphere", "Atmosphere");
			addChild($id, "biodiversity", "Biodiversity");
			addChild($id, "climate change", "Climate Change");
			addChild($id, "conservation", "Conservation");
			addChild($id, "environmental activism", "Environmental Activism");
			addChild($id, "forests", "Forests");
			addChild($id, "genetics", "Genetics");
			addChild($id, "nuclear issues", "Nuclear Issues");
			addChild($id, "oceans", "Oceans");
			addChild($id, "pollution", "Pollution");
			addChild($id, "renewable energy", "Renewable Energy");
			addChild($id, "rivers", "Rivers");
			addChild($id, "soils", "Soils");
			addChild($id, "rural life", "Rural Life");

			$id = addParent("health", "Health");
			addChild($id, "aids", "Aids");
			addChild($id, "disease/treatment", "Disease/Treatment");
			addChild($id, "infant mortality", "Infant Mortality");
			addChild($id, "malaria", "Malaria");
			addChild($id, "narcotics", "Narcotics");
			addChild($id, "nutrition/malnutrition", "Nutrition/Malnutrition");

			$id = addParent("human rights", "Human Rights");
			addChild($id, "civil rights/civil liberties", "Civil Rights/Civil Liberties");
			addChild($id, "disability", "Disability");
			addChild($id, "indigenous rights", "Indigenous Rights");
			addChild($id, "race politics", "Race Politics");
			addChild($id, "religion", "Religion");
			addChild($id, "sexuality", "Sexuality");
			addChild($id, "social exclusion", "Social Exclusion");

			$id = addParent("information & media", "Information & Media");
			addChild($id, "communications", "Communications");
			addChild($id, "culture", "Culture");
			addChild($id, "freedom of expression", "Freedom Of Expression");
			addChild($id, "internet", "Internet");
			addChild($id, "knowledge", "Knowledge");
			addChild($id, "media", "Media");
			addChild($id, "science", "Science");
			addChild($id, "art", "Art");
			addChild($id, "sport", "Sport");

			$id = addParent("politics", "Politics");
			addChild($id, "activism", "Activism");
			addChild($id, "civil society", "Civil Society");
			addChild($id, "codes of conduct", "Codes Of Conduct");
			addChild($id, "democracy", "Democracy");
			addChild($id, "ethics/value systems", "Ethics/Value Systems");
			addChild($id, "geopolitics", "Geopolitics");
			addChild($id, "globalisation", "Globalisation");
			addChild($id, "governance", "Governance");
			addChild($id, "justice/crime", "Justice/Crime");
			addChild($id, "law", "Law");
			addChild($id, "transparency/corruption", "Transparency/Corruption");
			addChild($id, "United Nations", "United Nations");
			addChild($id, "class issues", "Class Issues");

			$id = addParent("war & peace", "War & Peace");
			addChild($id, "arms/military", "Arms/Military");
			addChild($id, "conflict", "Conflict");
			addChild($id, "conflict resolution", "Conflict Resolution");
			addChild($id, "landmines", "Landmines");
			addChild($id, "nuclear issues", "Nuclear Issues");
			addChild($id, "peace", "Peace");
			addChild($id, "security", "Security");
			addChild($id, "terrorism", "Terrorism");
		}
		if (isset($install_delete_topic))
		{
			$conn = @pg_connect("host=$install_node_host port=$install_node_port dbname=$install_node_db_name user=$install_node_user password=$install_node_pass");
			$sql = "DELETE FROM sotf_topic_tree_defs";
			$result = @pg_query($conn, $sql);
			$sql = "DELETE FROM sotf_topic_trees";
			$result = @pg_query($conn, $sql);
			$sql = "DELETE FROM sotf_topics";
			$result = @pg_query($conn, $sql);
			@pg_close($conn);		//close old connection
		}
		$conn = @pg_connect("host=$install_node_host port=$install_node_port dbname=$install_node_db_name user=$install_node_user password=$install_node_pass");
		$sql = "SELECT COUNT(*) as rows FROM sotf_topic_tree_defs";
		$result = @pg_query($conn, $sql);
		$count = pg_fetch_array ($result);
		@pg_close($conn);		//close old connection
		if ($count["rows"] == 0)
		{
			$install_test_result[$id] = "Topic tree is empty";
			$install_color[$id] = $install_red;
		}
		else
		{
			$install_test_result[$id] = "OK";
			$install_color[$id] = $install_green;
		}
	}
	PrintTitle($id);
	print('<DIV ALIGN="center"><BR />
	<INPUT type="submit" name="delete_topic" value="Delete topic tree">
	<INPUT type="submit" name="create_topic" value="Create topic tree">
	</DIV>');
	PrintButton($id);

/*
	$id = ;	//////////////////////////Test 
	if (RunTest($id, ""))
	{
	
		if ()
		{
			$install_test_result[$id] = "";
			$install_color[$id] = $install_red;
		}
		else
		{
			$install_test_result[$id] = "OK";
			$install_color[$id] = $install_green;
		}
	}
	PrintTitle($id);
	PrintButton($id);
*/


?>

</TABLE>
</DIV>
<?php
	$install_erroronpage = false;			//test if any errors on page
	for ($i=1; $i <= $install_maxtests; $i++)	//run through all tests (nr.0 is no test)
	{
		if ($install_color[$i] != $install_green) $install_erroronpage = true;	//if not green set error true
	}
	if ($install_erroronpage) print('<DIV ALIGN="center"><BR /><BIG>Please run again the \'red marked\' tests!</BIG><BR /></DIV>');	//if no error write 'ALL OK'
	elseif ( ($install_node_user != $nodeDbUser) OR ($install_node_pass != $nodeDbPasswd)
		OR ($install_node_host != $nodeDbHost) OR ($install_node_port != $nodeDbPort)
		OR ($install_node_db_name != $nodeDbName) OR ($install_sadm_user != $userDbUser)
		OR ($install_sadm_pass != $userDbPasswd) OR ($install_sadm_host != $userDbHost)
		OR ($install_sadm_port != $userDbPort) OR ($install_sadm_db_name != $userDbName) )
		print('<DIV ALIGN="center"><BR /><BIG>The database settings here do not match with the setting in config.inc.php, please update it.</BIG><BR /></DIV>');	//if no error write 'ALL OK'
	else print('<DIV ALIGN="center"><BR /><BIG>ALL OK, you are now ready to use the <A HREF="../index.php">system</A>. Log in using the admin login.</BIG><BR /></DIV>');	//if no error write 'ALL OK'

?>
<DIV ALIGN="center">
<BR /><INPUT type="submit" name="RUN_ALL" value="RUN ALL">
</DIV>
</FORM>
</BODY>
</HTML>
