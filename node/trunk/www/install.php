<?php
/*

1. test file locations in config.inc.php:

tudsz-e bele irni vagy ha kod van ott, akkor be tudod-e tolteni?

log file-t tudod-e irni?

2. test SQL

van-e sadm db?

van-e admin user?

van-e node db?

ha nincs, letrehozni

3. streaming test:

- lame van-e, mukodik-e
- ogg van-e mukodik-e
- icecast van-e mukodik-e?

4. node setup

- nodeId unique-e?
- neighbour-ok....

- topic server test....

*/

/**
Connect to the DB and die if any error

PrintThis("Connecting to the DB: ", false);
$dsn = "pgsql://$user:$pass@$host/sadm";
$db = DB::connect($dsn);
if (DB::isError($db)) PrintThis("Can not find pgsql://$user@$host/sadm (".$db->getMessage().")", true, true);
	else PrintThis("done");
*/

/**
add administrator
PrintThis("Creating ADMIN account in table authenticate: ", false);
$sql = "INSERT INTO authenticate(username, passwd, realname, language, email, last_visit, num_logins) VALUES('god', 'password', 'Administrator', 'en', '', '2002-06-26 10:32:00+02', 90)";
$result = $db->query($sql);
if (DB::isError($result)) PrintThis($result->getMessage(), true, true);	//die ($result->getMessage());
	else PrintThis("done");

PrintThis("Adding user_prefs for ADMIN: ", false);
$sql = "INSERT INTO USER_PREFERENCES(username, passwd, realname, language, email, last_visit, num_logins) VALUES('god', 'password', 'Administrator', 'en', '', '2002-06-26 10:32:00+02', 90)";
$result = $db->query($sql);
if (DB::isError($result)) PrintThis($result->getMessage(), true, true);	//die ($result->getMessage());
	else PrintThis("done");
*/


/**
Search for an unused name for the DB and create it

PrintThis("Creating database: ", false);
$i = 1;
$new_db_name = $db_name;
do
	{
	$i++;
	$sql = "CREATE DATABASE $new_db_name";
	$result = $db->query($sql);
	if (DB::isError($result)) $new_db_name = $db_name.$i;
	if ($i == 11) break;
	}
while (DB::isError($result));
if ($i == 11) PrintThis("Can not create DB", true, true);
	else PrintThis("done, the new DB name is '".$new_db_name."'");
*/	



/*Connect to the new DB
$dsn = "pgsql://$user:$pass@$host/$new_db_name";
PrintThis("Connecting to the new DB: ", false);
$db = DB::connect($dsn);
if (DB::isError($db)) PrintThis("Can not find pgsql://$user@$host/$db_name (".$db->getMessage().")");
	else PrintThis("done");
*/

/*
//Read SQL commands from db2.sql and execute them
$buffer = "";
$fd = @fopen ("../code/doc/db.sql", "r");
if (!$fd) die("SQL file not found");
while (!feof ($fd)) {
    $buffer = $buffer.fgets($fd,10);
}
//echo $buffer;
fclose ($fd);


$buffer = rtrim($buffer);
$sql = explode(";", $buffer);

$max = count($sql);
for($i=0; $i<$max; $i++) if ($sql[$i] != '')
	{
	$result = $db->query($sql[$i]);
	if (DB::isError($result))
		{
		print("<BR><BR>----------------------------------<BR>".$sql[$i]);
		print($result->getMessage());
		}
	}

*/


function PrintTitle($number)
{
	global $install_test_name;
	print('<DIV ALIGN="center"><B>'.$install_test_name[$number].'<BR /><BR /></B></DIV>');
}

function PrintButton($number)
{
	global $install_test_result, $install_color;
	print('<BR /><DIV ALIGN="center">'.$install_test_result[$number].'</DIV>');
	print('<INPUT type="hidden" name="test_result[]" value="'.$install_test_result[$number].'">');
	print('<INPUT type="hidden" name="color[]" value="'.$install_color[$number].'">');
	print('<BR /><DIV ALIGN="center"><INPUT type="submit" name="run_test" value="Run test '.$number.'"></DIV>');
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

function GetPerm($filename)
{
	if (file_exists($filename))		//if exists read only
	{
		if (!$fp = fopen($filename, 'r'))
		{
			return("Read ERROR.");
		}
		fclose($fp);
		return("Readable");
	}
	else					//if not try to create it
	{
		if (!$fp = fopen($filename, 'a'))
		{
			return("Creating ERROR.");
		}
		if (!fwrite($fp, "alma"))
		{
			return("Write ERROR.");
		}
		fclose($fp);
		unlink($filename);
		return("Writeable");
	}
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
$install_db_name = $HTTP_POST_VARS["db_name"];		//DB name

$install_run_test = $HTTP_POST_VARS["run_test"];	//Run test X buttons
$install_run_all = $HTTP_POST_VARS["RUN_ALL"];		//Run all button

$install_color = $HTTP_POST_VARS["color"];		//color values for the cells
$install_test_result = $HTTP_POST_VARS["test_result"];	//result strings of the tests


if ($install_user === NULL)		//set default parameter if first time here
{
	$install_user = 'micsik';
	$install_pass = 'aafa';
	$install_host = 'samsonn';
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




/*
* Run tests
*/

//Test 1
$id = 1;
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


//Test 2
$id = 2;
if (RunTest($id, "'config.inc.php' file include test"))
{
	$error = false;
	$install_test_result[$id] = "config.inc.php: ";

	if (!file_exists("config.inc.php"))
	{
		$install_test_result[$id] .= "file not exists.";
		$error = true;
	}
	elseif (GetPerm("config.inc.php") != "Readable")
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
		if ($nodeDbHost != NULL)
		{
			$install_color[$id] = $install_green;
			$install_test_result[$id] .= "OK";
		}
		else
		{
			$install_color[$id] = $install_red;
			$install_test_result[$id] .= "include error";
		}
	}
}
if (($install_color[$id] = $install_green) AND ($nodeDbHost == NULL))			//if test 2 passed and not already included
	@include("config.inc.php");

//Test 3
$id = 3;
if (RunTest($id, "Directory and file permissions", 2))
{
	$install_test_result[$id] = "";
	$install_test_result[$id] .= "".GetPerm("aaaa.aaa");
	if (false)
	{
		$install_color[$id] = $install_red;
	}
	else
	{
		$install_color[$id] = $install_green;
	}
}


/*
//Test 
$id = ;
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
*/

//Test 4
$id = 4;
if (RunTest($id, "PostGresql connection"))
{
	$conn = @pg_connect("host=$install_host user=$install_user dbname=template1 password=$install_pass port=$install_port");
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

//Test 5
$id = 5;
if (RunTest($id, "DB connection to 'sadm'", 4))		//test 4 should be OK to run this test
{
	$conn = @pg_connect("host=$install_host dbname=sadm user=$install_user password=$install_pass port=$install_port");
	if (!$conn)
	{
		$install_test_result[$id] = "Database 'sadm' not found, please install SADM";
		$install_color[$id] = $install_red;
	}
	else
	{
		$install_test_result[$id] = "OK";
		$install_color[$id] = $install_green;
	}
	@pg_close($conn);
}

//Test 6
$id = 6;
if (RunTest($id, "DB connection", 5))		//test 4 should be OK to run this test
{
	$conn = @pg_connect("host=$install_host dbname=$install_db_name user=$install_user password=$install_pass port=$install_port");
	if (!$conn)
	{
		$install_test_result[$id] = "Database '".$install_db_name."' not found";
		$install_color[$id] = $install_red;
	}
	else
	{
		$install_test_result[$id] = "OK";
		$install_color[$id] = $install_green;
	}
	@pg_close($conn);
}

@include "mama";

?>
<HTML>
<HEAD>
<TITLE>Install</TITLE>
</HEAD>
<BODY>
<DIV ALIGN="center"><H2>Install</H2></DIV>
<FORM method="post" action="install.php">
<INPUT type="hidden" name="test_result[]" value="<?php print($install_test_result[0]) ?>"></DIV>
<INPUT type="hidden" name="color[]" value="FFFFFF">

<TABLE BORDER=1 CELLPADDING=5 CELLSPACING=0 WIDTH="100%" BGCOLOR="<?php print($install_color[0]) ?>">
	<TR>
		<TD BGCOLOR="<?php print($install_color[1]) ?>" width="33%">
		<?php PrintTitle(1) ?>
		<?php PrintButton(1) ?>
		</TD>
		<TD BGCOLOR="<?php print($install_color[2]) ?>" width="33%">
		<?php PrintTitle(2) ?>
		<?php PrintButton(2) ?>
		</TD>
		<TD BGCOLOR="<?php print($install_color[3]) ?>" width="33%">
		<?php PrintTitle(3) ?>
		<?php PrintButton(3) ?>
		</TD>
	</TR>
	<TR>
		<TD BGCOLOR="<?php print($install_color[4]) ?>" width="33%">
		<?php PrintTitle(4) ?>
		<DIV ALIGN="center">
		Username: <INPUT type="text" name="user" value="<?php print($install_user) ?>"><BR />
		Password: <INPUT type="password" name="pass" value="<?php print($install_pass) ?>"><BR />
		Hostname: <INPUT type="text" name="host" value="<?php print($install_host) ?>"> Port: <INPUT type="text" name="port" value="<?php print($install_port) ?>" SIZE=5><BR />
		</DIV>
		<?php PrintButton(4) ?>
		</TD>
		<TD BGCOLOR="<?php print($install_color[5]) ?>" width="33%">
		<?php PrintTitle(5) ?>
		<?php PrintButton(5) ?>
		</TD>
		<TD BGCOLOR="<?php print($install_color[6]) ?>" width="33%">
		<?php PrintTitle(6) ?>
		Database name: <INPUT type="text" name="db_name" value="<?php print($install_db_name) ?>"><BR />
		<?php PrintButton(6) ?>
		</TD>
	</TR>
</TABLE>

<DIV ALIGN="center"><BR /><INPUT type="submit" name="RUN_ALL" value="RUN ALL"></DIV>
</FORM>
</BODY>
</HTML>
