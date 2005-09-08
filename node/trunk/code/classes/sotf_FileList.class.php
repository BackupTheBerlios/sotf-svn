<?php

require_once($config['getid3dir'] . "/getid3.php");
require_once("sotf_File.class.php");
require_once("sotf_AudioFile.class.php");

// $Id$

/**
* This is a class for handling list of files.
*
* @author	Tamas Kezdi SZTAKI DSD <tbyte@sztaki.hu>
* @package	StreamOnTheFly
* @version	0.1
*/
class sotf_FileList
{
	/**
	* List of sotf_File or sotf_AudioFile objects.
	*
	* @attribute 	array	$list
	* @see	{@link sotf_File}, {@link sotf_AudioFile}
	*/
	var $list = array();

	/**
	* Checks whether the specified file is exist in the list.
	*
	* @param	string	$path	Path of the file to be checked
	* @return	boolean	If there is a file in the list that has the same path returns true, else false
	*/
	function pathExist($path)
	{
		$path = realpath(trim($path));
		reset($this->list);
		for ($i=0;$i<count($this->list);$i++)
		{
			if ($this->list[$i]->getPath() == $path)
				return true;
		}
		return false;
	} // end func pathExist


  /** Returns the count of files in list */
  function count() {
    return count($this->list);
  }

	/**
	* Adds a file to the list.
	*
	* @param	string	$path	Path of the file to add to the list
	* @return	boolean	If the file was successfully added to the list return true, else false
	*/
	function add($path)
	{
		$path = realpath(trim($path));
		if (is_file($path))
		{
			if (!$this->pathExist($path))
			{
				$audioinfo = GetAllFileInfo($path);
				if (isset($audioinfo["audio"]))
					$this->list[] = & new sotf_AudioFile($path);
				else
					$this->list[] = & new sotf_File($path);
			}
		}
		return false;
	} // end func add

	/**
	* Removes a file from the list.
	*
	* @param	string	$path	Path of the file to remove from the list
	* @return	boolean	If the file was successfully removed from the list return true, else false
	*/
	function remove($path)
	{
		$path = realpath(trim($path));
		$list = array();
		$retval = false;
		reset($this->list);
		for ($i=0;$i<count($this->list);$i++)
		{
			if ($this->list[$i]->getPath() != $path)
				$list[] = & $this->list[$i];
			else
				$retval = true;
		}
		if ($retval)
			$this->list = & $list;
		return $retval;
	} // end func remove

	/**
	* Removes all non-audio files from the list.
	*/
	function removeNonAudio()
	{
		$paths = array();
		for ($i=0;$i<count($this->list);$i++)
			if (!$this->list[$i]->isAudio())
				$paths[] = $this->list[$i]->getPath();
		for ($i=0;$i<count($paths);$i++)
			$this->remove($paths[$i]);
	} // end func removeNotAudio

	/**
	* Creates the list from a directory.
	*
	* @param	string	$path	Path of the directory
	* @return	boolean	If the list was successfully created return true, else false
	*/
	function getDir($path, $prefix='')
	{
		$path = realpath(trim($path));
		$list = array();
		$retval = false;
		if (is_dir($path))
			if ($handle = opendir($path))
			{
        while (false !== ($filename = readdir($handle))) {
          if(!$prefix || preg_match("/^$prefix/", $filename))			
		  
		  	// START ----- added by buddhafly 05-08-30
			if(!preg_match('/^\./', $filename)){

				 $extension = substr($filename, strrpos($filename, '.') +1);

               		 $restname = substr($filename, 0, (-1*(strlen($extension)+1)));
               		 
               		 
               		$newname = convert_special_chars($restname);

				$newname .= "." . $extension;
 
				rename($path . '/' . $filename, $path . '/' . $newname);
				$filename = $newname;
			}
			// END ------- added by buddhafly 05-08-30
			
			$this->add($path . '/' . $filename);
        }
        closedir($handle); 
				$retval = true;
      }
		return $retval;
	} // end func getDir

	/**
	* Creates the list from all audio files in the specified directory.
	*
	* @param	string	$path	Path of the directory
	* @return	boolean	If the list was successfully created return true, else false
	*/
	function getAudioFromDir($path, $prefix='')
	{
		$retval = $this->getDir($path, $prefix);
		$this->removeNonAudio();

		return $retval;
	} // end func getDir

	/**
	* Unlink file and removes it from the list.
	*
	* @param	string	$path	Path of the file to be deleted
	* @return	boolean	If the file was successfully deleted
	*/
	function unlink($path)
	{
		$path = realpath(trim($path));
		if (unlink($path))
			return $this->remove($path);
		return false;
	} // end func unlink

	/**
	* Gets the filenames
	*
	* @return	array	Array of name of files
	*/
	function getFileNames()
	{
		$list = array();
		for ($i=0;$i<count($this->list);$i++)
			$list[] = & $this->list[$i]->name;
		sort($list);
		return $list;
	} // end func getFileNames

	/**
	* Gets the files
	*
	* @return	array	Array of sotf_File objects
	*/
	function getFiles()
	{
    return $this->list;
	} // end func getFiles


} // end class sotf_FileList

?>
