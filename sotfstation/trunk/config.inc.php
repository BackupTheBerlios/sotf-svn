<?
	/****************
	* SOTF Station Management Tool
	*********************************
	* Author: Kulikov Alexey - alex@pvl.at, alex@ita-studio.com
	*********************************
	* Please bear in mind, that this software was written for _fun_ =) 
	*************************/

	/************************
	* Configuration File :: 07.11.2002
	*----------------------------------------
	* Feel free to edit all the setting in this file, but please
	* take care, since the application depends on these few lines
	* of code ;)
	* 
	* An important note: to spare resources and the avoid the need
	* of declaration of global variables constants are used in order
	* to represent global values. Constants are defined as follown
	* 
	*    define(sting name, mixed value[, int case sensitive]);
	************************/
	
	//Database Access Parameters, read http://pear.php.net for docs
	define('DB_TYPE','pgsql');																# Type of Database :: accepted (string)
	define('DB_HOST','localhost');														# Where the Database is located :: accepted (string)
	define('DB_NAME','sotf_station');													# Database Name :: accepted (string)
	define('DB_USER','Dolce');																# Database User :: accepted (string)
	define('DB_PASS','');																			# Database Access Password :: accepted (string)
	
	//Names
	define('STATION_NAME','Da Station');											# Name of this station
	define('HOME_NAME','Home');																# Name of the homepage
	
	//Paths
	define('TPL_DIR','templates');														# Templates are found in this directory (relative) :: accepted (string)
	define('SRC_ROOT','http://localhost/work/sotfstation/');	# The program is located under this URL (absolute) :: accepted (string)
	
	define('PEAR_PATH','');																		# Path to Pear, by default it is empty, if PHP has been compiled correctly :: accepted (string)
	
	define('SMARTY_PATH','Smarty');														# Path to the template parser, by default it is included with this distro
																														# and you don't have to edit this entry, but you may wish to use the external
																														# library. (relative) :: accepted (string)
	
	define('XMLRPC_PATH','xmlrpc');														# Path to the XMLRPC routines, by default these are included with this distro
																														# and you don't have to change this entry, but you may with to use an external
																														# library. (relative) :: accepted (string)
	
	//Other Settings
	define('ALLOW_GZIP',TRUE);																# Either allow or dissallow output compression :: accepted (bool)
	define('NOW',time());																			# Current Timestamp :: accepted (int)
?>