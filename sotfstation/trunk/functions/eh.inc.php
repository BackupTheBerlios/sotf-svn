<?
	/**
	 * eh() - CUSTOM ERROR HANDLER
	 * 
	 * Purpose: to override PHP's error handling function and log errors if needed
	 * 
	 * @param $type
	 * @param $msg
	 * @param $file
	 * @param $line
	 * @param $context
	 * @return 
	 */
	function eh($type, $msg, $file, $line, $context)
	{
		//this function will need further development
		echo $type . "<br>" . $msg . "<br>" . $file . "<br>" . $line . "<br>" . $context;
	}
?>