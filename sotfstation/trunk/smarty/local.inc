<?php
/*  PVL SystemAdmin Configs */

/*  DB Connection Parameters good for PEAR DB */

	$user = 'Dolce'; 
	$pass = ''; 
	$host = 'localhost'; 
	$db_name = 'temp'; 
	$dsn = "pgsql://$user:$pass@$host/$db_name"; 

/* session stuff */

    //ini_alter("session.auto_start","1");
/*	session_name("PVLAdmin");*/

?>
