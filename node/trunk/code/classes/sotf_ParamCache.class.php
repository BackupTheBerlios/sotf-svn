<?php
/**
* class to prevent user from repost data with reload
*
* @author Mate Pataki MTA SZTAKI DSD
*
*/
class sotf_ParamCache
{
	var $TIMEID;
	var $MAXCACHE = 15;

	function sotf_ParamCache($redir = true)			//constuctor
	{
		session_start();				//if not started yet
		//session_unset();				//debug
		if (isset($_SESSION['ParamCacheMaxCache'])) $this->MAXCACHE = $_SESSION['ParamCacheMaxCache'];	//get maxcache
		if ($this->MAXCACHE != 0)			//if cache limiter is enabled
		{
			$count = 0;				//set to 0
			$all = array_keys($_SESSION);		//get all keys
			$max = count($all);			//count keys
			for ($i = 0; $i < $max; $i++)		//go throug all
				if (strpos($all[$i], "PCID") === 0) $count++;	//count PCID keys
			/*
			if ($count > $this->MAXCACHE)	//TODO: debug only
			{
			$fp = fopen("tmp/paramCache.log", 'a');			//create file
			fwrite($fp, "-------------------------\n");		//write string
			fwrite($fp, "PHP_SELF=".$_SERVER['PHP_SELF']."\n");	//write string
			fwrite($fp, "REMOTE_ADDR=".$_SERVER['REMOTE_ADDR']."\n");	//write string
			fwrite($fp, "HTTP_REFERER=".getenv(HTTP_REFERER)."\n");	//write string
			fwrite($fp, "MAXCACHE=".$this->MAXCACHE."\n");		//write string
			fwrite($fp, "current=".$count."\n");			//write string
			fclose($fp);					//clode file
			}
			*/
			if ($count > $this->MAXCACHE)		//if too mutch data in session
				for ($i = 0; $count > $this->MAXCACHE; $i++)
				if (strpos($all[$i], "PCID") === 0)
				{
					session_unregister($all[$i]);
					$count--;
				}
		}

		$this->TIMEID = $_GET["PCID"];			//get the ID variable
		if ( isset($this->TIMEID) AND !isset($_SESSION[$this->TIMEID]) ) unset($this->TIMEID);	//user opened a second window, need a new ID
		if ( (count($_POST) == 0) AND isset($this->TIMEID) ) return $this->TIMEID;	//return ID (reload can be pressed no problem)

		$this->TIMEID = "PCID".substr(time(), -10);			//crate new ID, max length 10

		if (isset($_POST["PCOLDID"])) session_unregister($_POST["PCOLDID"]);	//delete old ID if any defined in the form itself
		$pos = strpos(getenv(HTTP_REFERER), "PCID=");		//the old ID in the referrer page
		if ($pos != false)			//if PCID found
			session_unregister(substr(getenv(HTTP_REFERER), $pos+5, 14));	//delete old ID if any
		if (count($_POST) == 0)
			{
				session_unregister("PCID1037288531");	//delete standard old ID if any
				$this->TIMEID = PCID1037288531;		//nothing pressed, set standard ID always
				return $this->TIMEID;			//if no data posted return new ID
			}

		$_SESSION[$this->TIMEID]["POST"] = $_POST;	//save posted data
		$_SESSION[$this->TIMEID]["FILES"] = $_FILES;	//save posted data
		$_SESSION[$this->TIMEID]["PROCESSED"] = false;	//set processed to false (can be used by the user)

		if (!$redir) return($this->TIMEID);			//if radir false return ID
		header ("Location: ".$PHP_SELF."?PCID=".$this->TIMEID."\r\n");	//else redirect page to the same with ID set
		die(0);							//exit
	}

	function setMaxCache($value = true)			//set MAXCACHE (the value for the maximal nr of IDs in SESSION)
	{
		$this->MAXCACHE = $value;
		$_SESSION['ParamCacheMaxCache'] = $value;
	}

	function getHiddenField()			//gives back the hidden field
	{
		return("<input type=\"hidden\" name=\"PCOLDID\" value=".$this->TIMEID.">");
	}

	function setProcessed($value = true)			//if this is set the user does not need to do it again
	{
		$_SESSION[$this->TIMEID]["PROCESSED"] = $value;
	}

	function getProcessed()					//ask processed status
	{
		$PROCESSED = $_SESSION[$this->TIMEID]["PROCESSED"];
		if (isset($this->TIMEID) AND isset($PROCESSED)) return $PROCESSED;	//if set return value
		else return false;		//else return false
	}

	function setParameter($name, $value)			//to add or overwrite parameters from "posted" data
	{
		$_SESSION[$this->TIMEID]["POST"][$name] = $value;
	}

	function getRegistered($name)				//gives back variables from POST and SetParameter
	{
		$value = $_SESSION[$this->TIMEID]["POST"][$name];
		if (!isset($value)) return NULL;
		return $value;
	}

	function getFiles($name, $data = NULL)				//gives back variables from FILES
	{
		if ($data === NULL) $value = $_SESSION[$this->TIMEID]["FILES"][$name];
			else $value = $_SESSION[$this->TIMEID]["FILES"][$name][$data];
		if (!isset($value)) return NULL;
		return $value;

	}

	function addResult($name, $value)			//to add test results and other values
	{
		$_SESSION[$this->TIMEID]["RESULT"][$name] = $value;
	}

	function getResult($name = NULL)				//gives back results added with AddResult
	{
		if ($name === NULL) $value = $_SESSION[$this->TIMEID]["RESULT"];
			else $value = $_SESSION[$this->TIMEID]["RESULT"][$name];
		if (!isset($value)) return NULL;
		return $value;
	}
}
?>