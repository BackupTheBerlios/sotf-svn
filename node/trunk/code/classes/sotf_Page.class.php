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
	/** the URL to redirect to in case of errors */
	var $errorURL;
	/** if this page appears in a popup */
	var $popup = false;

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
		if(empty($loc_msg)) {
		  debug("Missing localization for", $msg); 
		  $loc_msg = $msg;
		}
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
	  debug("status msg", $msg);
	  if($localized)
		 $msg = $this->getlocalized($msg);
	  $_SESSION['statusMsgs'][] = $msg; 
	}
	
	function setTitle($title) {
	  global $smarty;
	  $smarty->assign('PAGETITLE',$this->getlocalized($title));
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
	  if($this->errors) {
		 $_SESSION['errorMsgs'] = $this->errors;
	  }
	  header ("Location: $url");
	  debug("REDIRECT", $url);
	  stopTiming();
	  $this->logRequest();
	  exit;
	}

	function redirectSelf()
	{
	  $url = getenv('REQUEST_URI');
	  $this->redirect($url);
	}

	function logRequest()
	{
		global $debug, $startTime, $totalTime, $PHP_SELF;
		$host = getHostName();
		error_log("$host: FINISHED IN $totalTime ms WITH " . getenv("REQUEST_URI"),0);
		//if($debug)
		//  error_log("*********************************************************************************\n",0);
	}

	function send($template = 'main.htm'){
		global $smarty, $totalTime;

		if($this->popup) {
		  $template = 'main_popup.htm';
		  $smarty->assign('POPUP', 1);
		}

		unset($_SESSION['halted']);
		// handle status messages
		$smarty->assign('STATUS_MESSAGES', $_SESSION['statusMsgs']);
		unset($_SESSION['statusMsgs']);
		$smarty->assign('ERROR_MESSAGES', $_SESSION['errorMsgs']);
		unset($_SESSION['errorMsgs']);
		
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
	  global $smarty, $localPrefix;
	  debug("sending error page");
	  $smarty->assign("ERRORS", $this->errors);
	  $smarty->assign("ERROR_URL", $this->errorURL);
	  $smarty->assign("REFERER", getenv('HTTP_REFERER'));
	  $this->send('error.htm');
	  exit;
	  /*
	  if($this->popup) {
		 $smarty->assign('POPUP', 1);
	  }
	  if($this->errors) {
		 $_SESSION['errorMsgs'] = $this->errors;
	  }
	  $url = getenv('HTTP_REFERER');
	  debug("referer", $url);
	  if(!$url || !strstr($url, $localPrefix))
		 $url = $this->errorURL;
	  if($_SESSION['halted'] || !$url ) {
		 debug("sending error page");
		 $smarty->assign("ERRORS", $this->errors);
		 $this->send('error.htm');
	  } else {
		 $_SESSION['halted'] = 1;
		 $this->redirect($url);
	  }
	  */
	  exit;
	}

	/*
	 * 1. param is the maximal number of results
	 * 2. param is the url from the page
	 * return value is an associative array with 4 fields: from, to, maxresults, limit (string to the end of a query to limit a pgsql query)
	*/
	function splitList($rp_count, $rp_url)
	{
		global $smarty, $sotfVars;
		$rp_maxresults = $sotfVars->get("hitsPerPage", 30);		//display maximal so many results
		//$rp_maxresults = 2;
		$rp_from = sotf_Utils::getParameter('rp_from');
		
		$rp_button = sotf_Utils::getParameter('rp');
	
		if ($rp_button == "rp_first") $rp_from = 1;			//if first button pressed
		if ($rp_button == "rp_prev") $rp_from -= $rp_maxresults;		//if prev button pressed
		if ($rp_button == "rp_next") $rp_from += $rp_maxresults;		//if next button pressed
		if ($rp_button == "rp_last") $rp_from = $rp_count - ($rp_count % $rp_maxresults) + 1;	//if last button pressed
		
		if (!isset($rp_from)) $rp_from = 1;			//if first time on page
		if ($rp_from > $rp_count) $rp_from -= $rp_maxresults;	//if no more results
		
		if ($rp_from < 1) $rp_from = 1;		//$from must bee min. 1
		
		$rp_to = $rp_from + $rp_maxresults - 1;			//set 'to' field
		if ($rp_to > $rp_count) $rp_to = $rp_count;			//if less then $maxresults
		
		if (strpos($rp_url, "?") === false) $rp_url .= "?rp_from=$rp_from";
		else $rp_url .= "&rp_from=$rp_from";
	
		$smarty->assign("rp_count", $rp_count);
		$smarty->assign("rp_to", $rp_to);
		$smarty->assign("rp_from", $rp_from);
		$smarty->assign("rp_url", $rp_url);
	
		if ($rp_to == $rp_count) $smarty->assign("rp_theend", true);
		if ($rp_from == 1) $smarty->assign("rp_thebeginning", true);
		
		$limit["from"] = $rp_from-1;
		$limit["to"] = $rp_to-1;
		$limit["maxresults"] = $rp_maxresults;
		$limit["limit"] = " LIMIT ".$rp_maxresults." OFFSET ".($rp_from - 1);
		return $limit;
		//print($query." LIMIT ".$maxresults." OFFSET ".($from - 1))
	}

}

?>
