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
	if(!is_writeable($filename))
	{
		warning("$filename not writeable!");
		return;
	}
	$fp = fopen($filename, "w");
	fwrite($fp, $contents);
	fclose($fp);
}

/***************************************************************************
Tbyte added
function   : erase -> deletes a file or directory, even if directory is not empty
Parameters : $file -> filename to be deleted
****************************************************************************/
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
		rmdir($file);
	}
	else
	{
		unlink($file);
	}
}
/***************************************************************************
Tbyte added
function   : getFileFromPath -> gets filename from full path
Parameters : $path -> full path
****************************************************************************/
function getFileFromPath($path)
{
	$path_parts = pathinfo(realpath($path));
	return $path_parts["basename"];
}

/** Same as unix tail
 */
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

/*
 function tail($file,$num) {



   global $tail_start_buf; // Global: max string length
   global $tail_record_lenght; // Global: approximate string length
   if ($tail_start_buf==0) $tail_start_buf=80;
   if ($tail_record_lenght==0) $tail_record_lenght=4096;
   $appxlen=$tail_start_buf;// approximate string length
   $flen=filesize($file);// file length
   $out=array();// $out is array to return
   $fp=@fopen($file,'r');
   if ($fp) {
     do {
       if ($num*$appxlen>$flen) $pos=0;
       else $pos=$flen-($num*$appxlen);
       $out= sotf_Utils::_readfile($fp,$pos,$num);
       $appxlen*=2;
     } while (count($out)!=$num && $pos!=0);
     fclose($fp);
   }
   return $out;
 }

 function _readfile($fp,$pos,$num) {
   global $tail_record_lenght;
   fseek($fp,$pos);
   $tmp=array();
   for ($i=0; !feof($fp); $i++) {
     $line=chop(fgets($fp,$tail_record_lenght));
     if (!$line) break;
     $tmp[$i]=$line;
   }
   $j=count($tmp)-$num;
   if ($pos!=0 && $j==0) {
     $j++;
   }
   if ($j<0) {
     $j=0;
     $xnum=$num-1;
   } else $xnum=$num;
   for ($i=0; $i<$xnum && $j<count($tmp); $i++,$j++) $out[$i]=$tmp[$j];
   error_log(implode("\nS",$out), 0);
   return $out;
 }
*/

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

function killUnsafeChars($text)
{
	return trim(strtr($text, "\\|;{}[]~`'\"!@#$%^&=:<>/", '                                        '));
}

function getFileSafeParameter($name)
{
	return sotf_Utils::killUnsafeChars(sotf_Utils::getParameter($name));
}

function getSQLSafeParameter($name)
{
	return sotf_Utils::clean(sotf_Utils::getParameter($name), true);
}

function getParameter($name)
{
	global $HTTP_GET_VARS;
	global $HTTP_POST_VARS;

	$val = $HTTP_POST_VARS[$name];
	if(!isset($val))
		$val = $HTTP_GET_VARS[$name];
	return $val;
}


/******************************************************************************
function   : clean  -> removes nasty things that hurt databases
Parameters : $dirty -> string or array to clean up
             $allow_html -> if true, then we don't convert HTML characters
                            like < and > into &gt; and &lt;
*******************************************************************************/
function clean ($dirty,$allow_html=false)
{
  if(empty($dirty))
    return NULL;
	if (is_array($dirty))
	{
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



/*************  MAIL UTILS  ********************/

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
  global $mailFromAddress;
  $success = mail($to, $subject, $body, "From: $mailFromAddress");
  if(!$success)
    error_log("could not send mail to $to with subject $subject", 0);
}

}

?>
