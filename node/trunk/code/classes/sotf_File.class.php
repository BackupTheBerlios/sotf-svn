<?php
/**
* Default mime type.
*
* @constant	string	DEFAULT_MIME_TYPE	
* @package	StreamOnTheFly
*/
define('DEFAULT_MIME_TYPE', 'application/octet-stream');

/**
* This is a class for handling files.
*
* @author	Tamas Kezdi SZTAKI DSD <tbyte@sztaki.hu>
* @package	StreamOnTheFly
* @version	0.1
*/
class sotf_File
{
	/**
	* Name of the file without path.
	*
	* @attribute 	string	$name
	*/
	var $name;

	/**
	* Absolute path of the file.
	*
	* @attribute 	string	$path
	*/
	var $path;

	/**
	* Type of the file.
	*
	* Can be "regular" or "audio" or "none".
	* @attribute 	string	$type
	*/
	var $type;

	/**
	* Mime type of the file.
	*
	* @attribute 	string	$mimetype
	*/
	var $mimetype;

	/**
	* Sets up sotf_File object.
	*
	* @constructor sotf_File
	* @param	string	$path	Path of the file
	*/
	function sotf_File($path)
	{
		$this->path = realpath(trim($path));
		if (is_file($this->path))
		{
			$path_parts = pathinfo($this->path);
			$this->name = $path_parts['basename'];
			$this->type = "regular";
			$this->mimetype = $this->determineMimeType($this->getExtension());
		}
		else
		{
			$this->type = "none";	// File does not exist.
		}
	} // end func sotf_File

	/**
	* Gets the mime type of the file.
	*
	* @param	string	$path	Path of the file
	* @return	string	Mime type
	*/
	function determineMimeType($type)
	{
		$config['mimetypes'] = array(
			'doc'	=> 'application/msword',
			'gif'	=> 'image/gif',
			'htm'	=> 'text/html',
			'html'	=> 'text/html',
			'jpg'	=> 'image/jpeg',
			'mp3'	=> 'audio/mp3',
			'm3u'	=> 'audio/x-mpeg',
			'ogg'	=> 'application/x-ogg',
			'pdf'	=> 'application/pdf',
			'png'	=> 'image/png',
			'ps'	=> 'application/postscript',
			'txt'	=> 'text/plain',
			'xls'	=> 'application/vnd.ms-excel');
		if ($config['mimetypes'][$type])
			return $config['mimetypes'][$type];
		else
			return DEFAULT_MIME_TYPE;
	} // end func determineMimeType

	/**
	* Gets extension of the file.
	*
	* @return	string	File extension
	*/
	function getExtension()
	{
		return substr(strrchr($this->path, '.'), 1);
	} // end func getExtension

	/**
	* Gets the path of the file.
	*
	* @return	string	Mime type
	*/
	function getPath()
	{
		return $this->path;
	} // end func getPath

	/**
	* Checks whether the file is an audio file.
	*
	* @return	boolean	If the file is an audio file returns true, else false
	*/
	function isAudio()
	{
		return ($this->type == "audio");
	} // end func getPath
} // end class sotf_File

?>