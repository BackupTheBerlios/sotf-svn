<?php
ini_set("max_execution_time", "90");
header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
//TODO:debug infok kikapcsolasa!!!

function PrintTitle($number)
{
	global $install_test_name, $install_color;
	print('<TR><TD BGCOLOR="'.$install_color[$number].'">');
	print('<DIV ALIGN="center"><B>'.$install_test_name[$number].'<BR /><BR /></B></DIV>');
}

function PrintButton($number)
{
	global $install_test_result, $install_color;
	print('<BR /><DIV ALIGN="center">'.$install_test_result[$number].'</DIV>');
	print('<INPUT type="hidden" name="test_result[]" value="'.$install_test_result[$number].'">');
	print('<INPUT type="hidden" name="color[]" value="'.$install_color[$number].'">');
	print('<BR /><DIV ALIGN="center"><INPUT type="submit" name="run_test" value="Run test '.$number.'"></DIV>');
	print('</TD></TR>');
	flush();
}

function RunTest($number, $testname, $required = -1)
{
	global $install_run_test, $install_test_result, $install_test_name, $install_green, $install_color;
	$install_test_name[$number] = $testname;
	if ($required == -1)
		return (($install_run_test == "Run test ".$number) OR ($install_test_result[$number] == NULL));
	else
		return ( ($install_run_test == "Run test ".$number) OR (($install_test_result[$number] == NULL) AND ($install_color[$required] == $install_green)) );

}

function GetPerm($filename, $option)
{
	if ($option == "read")
	{
		if (file_exists($filename))
		{
			if (!$fp = fopen($filename, 'r'))
			{
				return("read ERROR");
			}
			fclose($fp);
			return("OK readable");
		}
		else					//if not try to create it
		{
		return("ERROR file does not exists");
		}
	}
	elseif ($option == "write")
	{
		if (file_exists($filename)) return("ERROR file in use");
		if (!$fp = fopen($filename, 'a'))
		{
			return("creating ERROR");
		}
		if (!fwrite($fp, "alma"))
		{
			return("write ERROR");
		}
		fclose($fp);
		unlink($filename);
		return("OK writeable");
	}
	elseif ($option == "dir")
	{
		if (file_exists($filename))
		{
			if (!$dir = @opendir($filename))
			{
				return("read ERROR");
			}
			closedir($dir);
			return("OK readable");
		}
		else					//if not try to create it
		{
		return("ERROR directory does not exists");
		}
	}
	elseif ($option == "append")
	{
		if (!$fp = fopen($filename, 'a'))
		{
			return("creating ERROR");
		}
		if (!fwrite($fp, "\ninstall scipt try\n"))
		{
			return("write ERROR");
		}
		fclose($fp);
		return("OK writeable");
	}
	else return "ERROR ".$option." unknown in GetPerm";
}


/*
Initial parameters
*/
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
$install_createdb = $HTTP_POST_VARS["createdb"];		//Create sadm db button

$install_color = $HTTP_POST_VARS["color"];		//color values for the cells
$install_test_result = $HTTP_POST_VARS["test_result"];	//result strings of the tests


if ($install_user === NULL)		//set default parameter if first time here
{
	$install_user = 'micsik';
	$install_pass = 'aafa';
	$install_host = 'samsonnn';
	$install_port = '5432';
	$install_db_name = 'node';

	for ($i=0; $i <= 9; $i++)
	{
		$install_color[$i]=$install_blue;
		$install_test_result[$i] = NULL;
	}
}

if ($install_run_all != NULL)		//if run_all button pressed
{
	for ($i=0; $i <= 9; $i++)
	{
		$install_color[$i]=$install_blue;
		$install_test_result[$i] = NULL;
	}
}


if (($install_color[$id] = $install_green) AND ($nodeDbHost == NULL))			//if test 2 passed and not already included
	@include("config.inc.php");

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
	if (RunTest($id, "PHP global variables"))
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
	
		if (!file_exists("config.inc.php"))
		{
			$install_test_result[$id] .= "file not exists.";
			$error = true;
		}
		elseif (GetPerm("config.inc.php", "read") != "OK readable")
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
			@include("config.inc.php");
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
		$install_test_result[$id] .= "tmp (./tmp) ".@GetPerm("./tmp/pmppmp.pmp", "write")."<BR />";
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
		$install_test_result[$id] .= "classdir ($classdir/error_Control.class.php) ".@GetPerm($classdir."/error_Control.class.php", "read")."<BR />";	//require($classdir . '/error_Control.class.php');
		$install_test_result[$id] .= "classdir ($classdir/sotf_Utils.class.php) ".@GetPerm($classdir."/sotf_Utils.class.php", "read")."<BR />";		//require($classdir . '/sotf_Utils.class.php');
		$install_test_result[$id] .= "classdir ($classdir/sotf_User.class.php) ".@GetPerm($classdir."/sotf_User.class.php", "read")."<BR />";		//require($classdir . '/sotf_User.class.php');
		$install_test_result[$id] .= "classdir ($classdir/sotf_Page.class.php) ".@GetPerm($classdir."/sotf_Page.class.php", "read")."<BR />";		//require($classdir . '/sotf_Page.class.php');
		$install_test_result[$id] .= "classdir ($classdir/sotf_Permission.class.php) ".@GetPerm($classdir."/sotf_Permission.class.php", "read")."<BR />";	//require($classdir . '/sotf_Permission.class.php');
		$install_test_result[$id] .= "classdir ($classdir/sotf_Id.class.php) ".@GetPerm($classdir."/sotf_Id.class.php", "read")."<BR />";		//require($classdir . '/sotf_Id.class.php');
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
			$install_test_result[$id] = "Database 'sadm' not found, please install SADM";
			$install_color[$id] = $install_red;
		}
		else
		{
			$sql = "select count(*) from authenticate where username = 'admin'";
			$result = @pg_exec($conn, $sql);
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
	PrintTitle($id);
	print('
	Username: <INPUT type="text" name="sadm_user" value="'.$install_sadm_user.'"><BR />
	Password: <INPUT type="password" name="sadm_pass" value="'.$install_sadm_pass.'"><BR />
	Hostname: <INPUT type="text" name="sadm_host" value="'.$install_sadm_host.'"> Port: <INPUT type="text" name="sadm_port" value="'.$install_sadm_port.'" SIZE=5><BR />
	Database name: <INPUT type="text" name="sadm_db_name" value="'.$install_sadm_db_name.'"><BR />');
	if ( ($install_sadm_user != $userDbUser) OR ($install_sadm_pass != $userDbPasswd) OR ($install_sadm_host != $userDbHost)
		OR ($install_sadm_port != $userDbPort) OR ($install_sadm_db_name != $userDbName) );;
	
					
	PrintButton($id);


					$install_node_user = $nodeDbUser;		//username for DB
					$install_node_pass = $nodeDbPasswd;		//password for DB
					$install_node_host = $nodeDbHost;		//host for DB
					$install_node_port = $nodeDbPort;		//port for DB
					$install_node_db_name = $nodeDbName;		//DB name


	
	$id = 6;	//////////////////////////Test 6
	if (RunTest($id, "DB connection to 'node'", 5))		//////////////////////////Test 4 should be OK to run this test
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
			$result = @pg_exec($conn, $sql);
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
							$line = fgets($fd, 1024);
							if (substr($line, 0, 2) != "--")
								$buffer .= $line;	//if not comment
						}
						fclose ($fd);

						$buffer = rtrim($buffer);				//delete spaces from end
						$sql = explode(";", $buffer);				//divide into single commands

						$max = count($sql);					//count commands
						$install_test_result[$id] = "OK";
						for($i=0; $i<$max; $i++) if ($sql[$i] != '')		//execute all commands if not empty
						{
							$result = @pg_exec($conn, $sql[$i]);
							//$result = $db->query();
							if (!$result)
								{
								$install_test_result[$id] = "Error in db.sql.";
								$install_color[$id] = $install_red;
								print("<BR><BR>----------------------------------<BR>".$sql[$i]);
								}
						}
						if ($install_test_result[$id] == "OK") $install_color[$id] = $install_green;
					}
				}
			}
		}
	}
	PrintTitle($id);
	print('
	Username: <INPUT type="text" name="node_user" value="'.$install_node_user.'"><BR />
	Password: <INPUT type="password" name="node_pass" value="'.$install_node_pass.'"><BR />
	Hostname: <INPUT type="text" name="node_host" value="'.$install_node_host.'"> Port: <INPUT type="text" name="node_port" value="'.$install_node_port.'" SIZE=5><BR />
	Database name: <INPUT type="text" name="node_db_name" value="'.$install_node_db_name.'"><BR />');
	if ($install_color[$id] == $install_red) print('<DIV ALIGN="center"><BR /><INPUT type="submit" name="createdb" value="Create NODE db"></DIV>');
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
<DIV ALIGN="center">
<BR /><INPUT type="submit" name="RUN_ALL" value="RUN ALL">
</DIV>
</FORM>
</BODY>
</HTML>
