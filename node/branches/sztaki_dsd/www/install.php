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

require("init.inc.php");
$user = sotf_Utils::getParameter('user');
$pass = sotf_Utils::getParameter('pass');
$host = sotf_Utils::getParameter('host');
$db_name = sotf_Utils::getParameter('db_name');

$output[] = "";

/**
include Pear DB
*/
require_once 'DB.php';

function PrintThis($print, $enter = true, $error = false)
{
	global $output;
	if ($error) $string = ("<FONT color='red'>".$print."</FONT>");
		else $string = ("<B>".$print."</B>");
	$output[count($output)] = $output[count($output)].$string;
	if ($enter)  $output[] = "";
}


/**
parameters for connecting to the DB
*/
if ($user == NULL)		//set default parameter
{
	$user = 'micsik';
	$pass = '';
	$host = 'samson:5432';
	$db_name = 'node';
}

/**
Connect to the DB and die if any error
*/
PrintThis("Connecting to the DB: ", false);
$dsn = "pgsql://$user:$pass@$host/sadm";
$db = DB::connect($dsn);
if (DB::isError($db)) PrintThis("Can not find pgsql://$user@$host/sadm (".$db->getMessage().")", true, true);
	else PrintThis("done");

/**
add administrator
*/
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


/**
Search for an unused name for the DB and create it
*/
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
	



//Connect to the new DB
$dsn = "pgsql://$user:$pass@$host/$new_db_name";
PrintThis("Connecting to the new DB: ", false);
$db = DB::connect($dsn);
if (DB::isError($db)) PrintThis("Can not find pgsql://$user@$host/$db_name (".$db->getMessage().")");
	else PrintThis("done");


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

$smarty->assign("output", $output);
$smarty->assign("user", $user);
$smarty->assign("pass", $pass);
$smarty->assign("host", $host);
$smarty->assign("db_name", $db_name);

$page->send();

?>