<?php

/**
* This is a class for miscellanous utilities
*
* @author Andras Micsik SZTAKI DSD <micsik@sztaki.hu>
* @version 0.1
*/
class sotf_Utils
{

	///////////////////////////// FILE UTILS ///////////////////////////////////////////////

	function save($filename, $contents)
	{
    if (!$fp = fopen($filename, 'wb')) {
      raiseError("Cannot open file ($filename)");
    }
    if(empty($contents))
      raiseError("Nothing to write into file ($filename)");
    if (!fwrite($fp, $contents)) {
      raiseError("Cannot write to file ($filename)");
    }
		fclose($fp);
	}

	/**
	* Deletes a file or directory, even if directory is not empty.
	*
	* @param	string	$file	File or directory to be deleted
	*/
	function erase($file)
	{
		if (is_dir($file))
		{
			$handle = opendir($file);
			while($filename = readdir($handle))
			{
				if ($filename != "." && $filename != "..")
				{
					sotf_Utils::erase($file."/".$filename);
				}
			}
			closedir($handle);
			if(!rmdir($file))
        logger("Could not delete dir", $file);
		}
		else
		{
			if(!unlink($file))
        logger("Could not delete file", $file);
		}
	}

	/**
	* Gets the name of the file from a path string
	*
	* @param	string	$path	Path to be parsed
	* @return	string	Name of the file without the path
	*/
	function getFileFromPath($path)
	{
		$path_parts = pathinfo(realpath($path));
		return $path_parts["basename"];
	}

  function getFileInDir($dir, $filename) {
    if(empty($filename))
      raiseError("Filename is empty");
    if(!$path = realpath($dir . '/' . $filename)) {
      debug("no such file", $dir . '/' . $filename);
      raiseError("no_such_file");
    }
    /* TODO: this does not work under WIndows, because of / and \ differences
    if(!strstr($path, $dir)) {
      debug("path", $path);
      debug("dir", $dir);
      raiseError("Attempt to break out directory");
    }
    */
    return $path;
  }

	/** Same as unix tail */
	function tail($file, $chars) {
	  $fd = fopen ("$file", "r");
	  $flen = filesize($file);
	  $pos = $flen - $chars - 200;
	  if($pos > 0)
	    fseek($fd, $pos);
	  fgets($fd, 200);
	  while (!feof ($fd)) {
	    $buffer = fgets($fd, 4096);
	    echo $buffer;
	  }
	  fclose ($fd);
	}
	
	/////////////////////////////// STRING UTILS ////////////////////////////////////////////////////
	
	function appendWith($list, $item, $delim=", ")
	{
		if($list)
		{
			if($item)
				return $list . $delim . $item;
			else
				return $list;
		}
		else
			return $item;
	}
	
	///////////////////////////// CGI UTILS ////////////////////////////////////////////////////////
	
	function registerGlobalParameters()
	{
		for($i=0; $i<func_num_args(); $i++)
		{
			$varname = func_get_arg($i);
			global $$varname;
			$$varname = sotf_Utils::getParameter($varname);
		}
	}

	/**
	* Characters to replace in a query parameter to be used safely as filename
	*
	* @attribute 	array	$unsafeChars
	*/
  var $unsafeChars = array( "\\" => "_",
                             "/" => "_",
                             "|" => "_",
                             ";" => "_",
                             "{" => "_",
                             "}" => "_",
                             "[" => "_",
                             "]" => "_",
                             "~" => "_",
                             "`" => "_",
                             "'" => "_",
                             '"' => "_",
                             "!" => "_",
                             "@" => "_",
                             "#" => "_",
                             "\$" => "_",
                             "%" => "_",
                             "^" => "_",
                             "&" => "_",
                             "=" => "_",
                             ":" => "_",
                             "<" => "_",
                             ">" => "_"
                             );
	
	function killUnsafeChars($text)
	{
    return trim(strtr($text, $this->unsafeChars));
	}
	
	function getFileSafeParameter($name)
	{
		return sotf_Utils::killUnsafeChars(sotf_Utils::getParameter($name));
	}
	
	function getSQLSafeParameter($name)
	{
		return sotf_Utils::clean(sotf_Utils::getParameter($name), true);
	}
	
  function collectPathinfoParams() {
    global $pathinfoParams;
    $str = mygetenv('PATH_INFO');
    if(!$str)
      return;
    $a = explode('/', $str);
    while(list(,$v)=each($a)) {
      $p = explode('__', $v);
      if(count($p) == 1) {
        $pathinfoParams['id'] = $v;
      } else {
        $pathinfoParams[$p[0]] = $p[1];
      }
    }
    debug("PATHINFO PARAMS: ", $pathinfoParams);
  }

  /*
  function getPathinfoOrParameter($name) {
    $val = mygetenv('PATH_INFO');
    if($val) {
      return substr($val, 1);
    } else {
      return sotf_Utils::getParameter($name);
    }
  }
  */

	function getParameter($name)
	{
    global $pathinfoParams;

    $val = $pathinfoParams[$name];
    if(isset($val))
      return $val;
		$val = $_POST[$name];
		if(!isset($val))
			$val = $_GET[$name];
    // if(isset($val))
    //$val = sotf_Utils::decodeHTML($val);
    // TODO: strip_tags ???
    //debug("getParam: $name", $val);
		return $val;
	}

  /** does not work for some characters (e.g. in Hungarian, Czech and Greek). */
  function decodeHTML($string) {
    $string = strtr($string, array_flip(get_html_translation_table(HTML_ENTITIES, ENT_COMPAT)));
    $string = preg_replace("/&#([0-9]+);/me", "chr('\\1')", $string);
    return $string;
  }

  /** changes strings for HTML display (before passing it to Smarty
      templates, htmlspecialchars is not usable here because of chars like &#999; */ 
	function toHTML($strOrArray) {
		return str_replace(array('"','<', '>'), array('&quot;','&lt;', '&gt;'), $strOrArray);
  }

  /** this is used before saving a string into SQL database */
	function magicQuotes($str) {
    return addslashes(stripslashes($str));
  }

  /** this clears not allowed chars from a string (e.g. station name, series name) and truncates to allowed length */
  function makeValidName($str, $len) {
    if(empty($str))
      return '';
    $retval = preg_replace("/[^a-zA-Z0-9_-]/","_",$str);
    $retval = preg_replace("/[_-]+/","_", $retval);
    $retval = preg_replace('/_+$/','', $retval);
    $retval = preg_replace('/^_+/','', $retval);
    return substr($retval, 0, $len);
  }

	//function   : clean  -> removes nasty things that hurt databases
	//Parameters : $dirty -> string or array to clean up
	//             $allow_html -> if true, then we don't convert HTML characters
	//                            like < and > into &gt; and &lt;
	//
	function clean ($dirty,$allow_html=false)
	{
	  if(empty($dirty))
	    return NULL;
		if (is_array($dirty))
		{
      reset($dirty);
			while( list( $key, $val) = @each( $dirty ))
			{
				if ($allow_html)
				{
					$clean[$key] = str_replace("'","&#039;",(stripslashes($val)));
				}
				else
				{
					$clean[$key] = str_replace("'","&#039;",(htmlspecialchars(stripslashes($val))));
				}
			}
		}
		else
		{
			if ($allow_html)
			{
				$clean = str_replace("'","&#039;",(stripslashes($dirty)));
			}
			else
			{
				$clean = str_replace("'","&#039;",(htmlspecialchars(stripslashes($dirty))));
			}
		}
		return $clean;
	}
	
  /**
   * Returns a random string
   *
   * @param	integer	$pass_len	Length of the string
   * @return	string	This string can contain upper-case, lower-case, and numeric characters
   */
	function randString($pass_len = 10) {
    $allchars =
      'abcdefghijklnmopqrstuvwxyzABCDEFGHIJKLNMOPQRSTUVWXYZ0123456789';
    $string = '';
    
    mt_srand ((double) microtime() * 1000000);
    
    for ($i = 0; $i < $pass_len; $i++) {
      $string .= $allchars{mt_rand (0,strlen($allchars))};
    }
    return $string;
  } // end func randString

	///////////////////////////////  XML UTILS  ////////////////////////////////////////////////////////

    function isAssocArray($array) {
      if(!is_array($array))
        return false;
      $keys = array_keys($array);
      $tries = count($keys);
      if($tries > 4) $tries = 4;
      for($i=0; $i<$tries; $i++) {
        if(!is_int($keys[$i]))
          return true;
      }
      return false;
    }

    function writeXML($name, $array, $level=0) {
      if(empty($array)) {
        //return "\n" . str_repeat('  ',$level) . "<$name />";
        return "";
      }
      if(sotf_Utils::isAssocArray($array)) {
        $retval = "\n" .  str_repeat('  ',$level) . "<$name>";
        reset($array);
        while(list($key,$value) = each($array)) {
          $type = gettype($value);
          switch($type) {
          case 'array':
            $retval .= sotf_Utils::writeXML($key, $value, $level+1);
            break;
          default:
            $retval .=  "\n" . str_repeat('  ',$level+1) . "<$key>" . htmlspecialchars($value) . "</$key>";
          }
        }
        $retval .= "\n" .  str_repeat('  ',$level) . "</$name>";
      } else {
        reset($array);
        while(list($key,$value) = each($array)) {
          $type = gettype($value);
          switch($type) {
          case 'array':
            $retval .= sotf_Utils::writeXML($name, $value, $level);
            break;
          default:
            $retval .= "\n" .  str_repeat('  ',$level) . "<$name>" . htmlspecialchars($value) . "</$name>";
          }
        }
      }
      return $retval;
    }

	///////////////////////////////  URL UTILS  ////////////////////////////////////////////////////////

    /** Quick check for valid URL syntax.  */
    function is_valid_URL($url) {
      $parsed = @parse_url($url);
      if(!$parsed['host'])
        return false;
      // Could also try to open the URL...
      return true;
    }
	
	///////////////////////////////  MAIL UTILS  ////////////////////////////////////////////////////////
	
	function is_valid_email($address)
	{
		if (!$address)
		{
			return false;
		}
		if(eregi("([_\.0-9a-z-]+@)([0-9a-z][0-9a-z-]+\.)+([a-z]{2,3})", $address))
		{
			$ary_address = ($address);
			if ($ary_address[0])
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}
	
	function sendmail($to, $subject, $body) {
	  global $config;
	  $success = mail($to, $subject, $body, "From: " . $config['mailFromAddress']);
	  if(!$success)
	    error_log("could not send mail to $to with subject $subject", 0);
	}

  /** static: recursively deletes all content from the directory and the dir itself. */
  function delete($file) {
    if (file_exists($file)) {
      chmod($file,0777);
      if (is_dir($file)) {
        $handle = opendir($file); 
        while($filename = readdir($handle)) {
          if ($filename != "." && $filename != "..") {
            sotf_Utils::delete($file."/".$filename);
          }
        }
        closedir($handle);
        if(!rmdir($file)) {
          logError("Could not rmdir: $file");
          return false;
        }
      } else {
        if(!unlink($file)) {
          logError("Could not unlink: $file");
          return false;
        }
      }
    }
    return true;
  }


}

?>
