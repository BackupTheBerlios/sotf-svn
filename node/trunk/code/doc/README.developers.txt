
Skeleton example for scripts in node/www:

<?php
require("init.inc.php");

$st = new sotf_Station('orange');

$smarty->assign_by_ref('STATION', $st);
$page->send();

?>

Explanation:

- template processing steps: if the name of the script is ACTION.php, and user language is LANG then:
  - 'main.htm' template is used for $smarty->display (from code/templates)
  - section 'ACTION' is loaded from code/configs/LANG.conf
  - 'ACTION.htm' template is included into 'main.htm' 

- after init.inc.php is included, you have the following global objects:
  - $errorControl: instance of error_Control class: for collecting errors during execution
  - $db: instance of db_Wrap class: to access the node database
  - $userdb: instance of db_Wrap class: to access the user database (SADM)
  - $page: instance of sotf_Page class: helps in handling HTTP request and sending result
  - $user: instance of sotf_User class: the current user, null if not logged in
  - $repository: instance of sotf_Repository class: the programme repository
  - $lang: the currently used output language as string

- file includes:
  - most of the necessary classes are included in init.inc.php
  - if you need more classes, include them at the beginning of the file

- you can use the following functions for debugging:
  - dump: dumps an object or array onto the screen
  - debug: writes a message or object dump into log file

- error handling: 
  - for user errors (invalid parameter, etc.) handle it with own code
  - for correctable system errors (exceptions) use addError($msg):
    this saves the error into $errorControl and creates a log message)
  - for uncorrectable system errors use raiseError($msg) to display an error screen and exit

- request handling is finished by one of these functions:
  - $page->redirect(url)
  - $page->send()  (if template is not specified, 'main.htm' is used)
  - $page->sendPopup() (uses 'main-popup.htm')
  - raiseError()

- use smarty easily
  - don't convert data structures from one to another: assign objects to smarty
  - use $smarty->assign_by_ref for assigning objects

- localization
  - each script (ACTION.php) has its template as ACTION.htm and has
  its localizations in the [ACTION] section of the localization config
  file (LANG.conf)
  - these localized texts are available also in PHP using the
    $page->getlocalized() function
  - global part of LANG.conf is also loaded, but the value in the
    section overwrites the value with the same name in global part
  - the global part contains general localizations used in all pages

- documentation: use phpdocgen (http://www.arakhne.org/tools/phpdocgen/):

  - before classes:
  /**
  * This class represents an user.
  *
  * @author Your name MTA SZTAKI DSD
  * 
  * @package streamonthefly/node
  */

  - before constants
  /**
  * Updating delay of the User cookie (in minutes).
  *
  * @constant USER_COOKIE_UPDATE_DELAY
  * @package streamonthefly/node
  */

  - before class vars:
	/**
	* Absolute path of the file.
	*
	* @attribute 	string	$path
	*/

  - before functions:
   /**
     * Replies if the current user is same as
     * the specified user.
     *
     * @param object user is the user
     * @return <code>TRUE</code> if
     *     the specified user is the same
     *     as the current.
   */
    
    - all supported tags:

@attribute type identifier 
The current comment is for an class attribute named identifier. You must use the tag @class to specified the class in which this attribute is. 
@author text 
An author of the currently documented stuff is described by the text. 
{@block text} 
The specified text is considered as a unsplitable block of text. 
@brief [comment] 
Sets the brief description for the documented object. If this tag was not given, the first sentence of the description will be the brief description. 
@class identifier 
The currently documented stuff is a member of the class (attribute, method...), or is the class identifier. In this last case, you could use the tag @package to specified the package in which this class is (if not, the default package will be used). 
@constant [type] identifier 
The currently documented stuff is a global constant named identifier. You could use the tag @package to specified the package in which this constant is (if not, the default package will be used). 
@constructor identifier 
The currently documented stuff is the constructor for the class identifier. 
@copyright description 
Sets a copyright comment about the documented object. 
@date date 
Sets the creation (or last-update) date of the documented object. 
@deprecated [text] 
The currently documented stuff is deprecated. text is the explanation. 
@extends identifier 
The documented class inherites from the class identifier. You must use the tag @class to specified the child class. 
@function identifier 
The current comment is for a global function named identifier. You could use the tag @package to specified the class in which is this attribute (if not, the default package will be used). 
{@hash [key] [comment]} 
Ignored. 
@inherited identifier 
The documented class inherites from the class identifier. You must use the tag @class to specified the child class. 
{@link name [comment]} 
Adds a link to the documentation of name with the comment. 
@method [static] identifier 
The current comment is for an class method named identifier. You must use the tag @class to specified the class in which this method is. If "static" is present, it applies that this method is static, i.e. the use of the variable $this is not allowed. 
@package identifier 
The currently documented stuff is a member of the package identifier. 
@param [optional] type identifier [comment] 
Adds a parameter comment for the current documented stuff. The parameter has named identifier and is commented by comment. If optional is present, the parameter is optional. 
@return [type] comment 
Explains what is return by the current documented function. 
@see comment 
Gives a pointer to another documentation. If comment is a valid name, show a link to the corresponding documentation page. 
@since comment 
Explains from when the current comment is exists. 
@todo comment 
Adds a comment about something to do on the current documented object. 
@use name 
Indicates that the current function uses the global variable name. 
@variable type identifier 
The current comment is for a global variable named identifier. You could use the tag @package to specified the package in which this variable is (if not, the default package will be used). 
@verbatim 
The explanation of the current comment will be putted as-is in the generated documentation. The generated explanation will be enclosed by <PRE> and </PRE>. 
@version comment 
Describes the version of the documented object. 


