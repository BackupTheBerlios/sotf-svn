<?php  //-*- tab-width: 3; indent-tabs-mode: 1; -*-

class sotf_Page
{

	/** current user (for simplicity this is also accessible as a global) */
	var $user = '';
	/** the script name without .php: this controls the localaization files and subtemplate used in main Smarty template */
	var $action = 'index';
	/** the output language (for simplicity this is also accessible as a global) */
	var $lang;
	/** a Config_File object of Smarty containing localized texts */
	var $langConf;
	/** an array containing error messages */
	var $errors;

	function sotf_Page()
	{
		global $lang, $user, $outputLanguages, $smarty, $defaultLanguage;
		global $nodeId, $basedir, $lang, $rootdir, $imagedir, $smartyDebug, $debug;

		// start session
		session_start();

		// load user data
		if($_SESSION['userid'])
		{
			$this->user = new sotf_User($_SESSION['userid']);
		}
		// Currently it is not needed
		/*else
		{
			$this->user = new sotf_User();
		}*/
		$user = $this->user;

		// determine language
		if($this->user)
		  $lang = $this->user->language;
		if(!$lang && in_array($_SERVER['HTTP_ACCEPT_LANGUAGE'], $outputLanguages))
		  $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		if(!$lang)
		  $lang = $defaultLanguage;

		// load localization constants for language
		$this->loadLoc();

		// determine the action
		preg_match('/(\w+)\.php$/', $_SERVER['SCRIPT_NAME'], $m);
		$this->action = $m[1];

	}

	function forceLogin()
	{
		if(!$this->loggedIn())
		{
			$url = getenv("REQUEST_URI");
			$this->redirect("login.php?okURL=". urlencode($url));
			exit;
		}
	}

	// load localization constants
	function loadLoc()
	  {
	    global $lang, $smarty;
	    $this->langConf = new Config_File($smarty->config_dir);
	    $this->langConf->load_file("$lang.conf", true);
	  }

	function getlocalized($msg)
	  {
      global $lang;
      $langConf = $this->langConf;
	    $loc_msg = $langConf->get("$lang.conf", $this->action, $msg);
      if(empty($loc_msg))
        $loc_msg = $langConf->get("$lang.conf", NULL, $msg);
	    if(empty($loc_msg))
	      $loc_msg = "[[$msg]]";
	    return $loc_msg;
	  }

	/**
	 * Micsik added
	 * function   : getlocalized2 -> Returns translated string for $msg with substituted parameters
	 * Parameters : $msg     -> the messsage to be translated
             $p1, $p2 , $p3  -> parameters substituted at %1, %2, %3 resp.
	*/
	function getlocalizedWithParams($msg)
	  {
	    $loc_msg = $this->getlocalized($msg);
      //if('[[')....
      $arg_list = func_get_args();
      for ($i = 1; $i <count($arg_list); $i++)
        {
          $loc_msg = preg_replace("/%$i/", $arg_list[$i], $loc_msg);
        }
	    return $loc_msg;
	  }

	function addStatusMsg($msg, $localized = true) {
	  if($localized)
		 $msg = $this->getlocalized($msg);
	  $_SESSION['statusMsgs'][] = $msg; 
	}
	
	function getUser()
	{
		if(!isset($this->user))
			return getlocalized("not_logged_in");
		return $this->user;
	}

	function loggedIn()
	{
	  //return $_SESSION['username'];
	  return is_object($this->user) && !empty($this->user->id);
	}

	function logURL($urlprefix='', $txt='') {
	  global $nodeId;
	  $rnd = rand();
	  if(!empty($urlprefix) && substr($urlprefix, -1) != '/')
	    $urlprefix .= '/';
	  return "javascript:w=window.open('$urlprefix" . "log.php?$rnd#end','log$nodeId');w.focus();";
	}

	function redirect($url)
	{
		header ("Location: $url");
		debug("REDIRECT", $url);
		stopTiming();
		$this->logRequest();
		exit;
	}

	function logRequest()
	{
		global $debug, $startTime, $totalTime, $PHP_SELF;
		if(!$totalTime)
		  $totalTime = time() - $startTime;
		$host = getHostName();
		// if($sec > 1)
		error_log("$host: FINISHED IN $totalTime s WITH " . getenv("REQUEST_URI"),0);
		//if($debug)
		//  error_log("*********************************************************************************\n",0);
	}

	function send($template = 'main.htm'){
		global $smarty, $totalTime;

		// handle status messages
		$smarty->assign('STATUS_MESSAGES', $_SESSION['statusMsgs']);
		unset($_SESSION['statusMsgs']);
		
		stopTiming();
		$smarty->assign("totalTime", $totalTime);
		$smarty->display($template);
		$this->logRequest();
    //exit; //??
	}
	
	function sendPopup(){
	  $this->send('main_popup.htm');
	}	

	function halt($msg='') {
	  global $smarty;
	  $smarty->assign("ERRORS", $this->errors);
	  $this->send('error.htm');
	  exit;
	}

}

?>
