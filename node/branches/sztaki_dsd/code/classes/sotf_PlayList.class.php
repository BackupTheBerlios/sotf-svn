<?php

/**
* This is a class for handling playlist.
*
* @author	Tamas Kezdi SZTAKI DSD <tbyte@sztaki.hu>
* @package	StreamOnTheFly
* @version	0.1
*/
class PlayList
{
	/**
	* List of track IDs.
	*
	* @attribute 	array	$list
	*/
	var $list = array();

	/**
	* Checks whether the specified ID exists in the list.
	*
	* @param	string	$id	ID to be checked.
	* @return	boolean	If the ID exists returns true, else returns false.
	*/
	function idExist($id)
	{
		reset($this->list);
		for ($i=0;$i<count($this->list);$i++)
			if ($this->list[$i] == $id)
				return true;
		return false;
	} // end func idExist

	/**
	* Gives back the positions of the ID.
	*
	* @param	string	$id	ID to be checked.
	* @return	array	Contains positions of the ID.
	*/
	function getPos($id)
	{
		$positions = array();
		reset($this->list);
		for ($i=0;$i<count($this->list);$i++)
			if ($this->list[$i] == $id)
				$positions[] = $i;
		return $positions;
	} // end func getPos

	/**
	* Gives back the playlist.
	*
	* @return	array	Playlist array.
	*/
	function get()
	{
		return $this->list;
	} // end func get

	/**
	* Gives back a string contains the playlist.
	*
	* @param	string	$separator	String to be inserted among two elements.
	* @return	string	Playlist array.
	*/
	function toString($separator = "")
	{
		$retval = "";
		for ($i=0;$i<count($this->list)-1;$i++)
			$retval .= $this->list[$i] . $separator;
		$retval .= $this->list[count($i)];
		return $retval;
	} // end func get

	/**
	* Adds the ID to the list at the specified position.
	*
	* @param	string	$id	ID to be added.
	* @param	integer	$pos	Position of the new ID.
	*/
	function add($id,$pos=null)
	{
		$list = array();
		$added = false;

		for ($i=0;$i<count($this->list);$i++)
		{
			if ($i === $pos)
			{
				$list[] = $id;
				$added = true;
			}
			$list[] = $this->list[$i];
		}
		if (!$added)
			$list[] = $id;	// if the ID was not added to the list, adds it to the end
		$this->list = & $list;
	} // end func add
	
	/**
	* Adds the ID to the end of the list.
	*
	* @param	string	$id	ID to be added.
	*/
	function addEnd($id)
	{
		$this->list[] = & $id;
	} // end func addEnd
	
	/**
	* Adds the ID to the front of the list.
	*
	* @param	string	$id	ID to be added.
	*/
	function addFront($id)
	{
		$list = array();

		$list[] = & $id;
		for ($i=0;$i<count($this->list);$i++)
			$list[] = & $this->list[$i];
		$this->list = & $list;
	} // end func addFront

	/**
	* Removes all occurences of the ID.
	*
	* @param	string	$id	ID to be removed
	*/
	function remove($id)
	{
		$list = array();

		for ($i=0;$i<count($this->list);$i++)
			if ($this->list[$i] != $id)
				$list[] = & $this->list[$i];
		$this->list = & $list;
	} // end func remove

	/**
	* Removes the ID of the given position.
	*
	* @param	integer	$pos	Position of ID to be removed.
	*/
	function removePos($pos)
	{
		$list = array();

		for ($i=0;$i<count($this->list);$i++)
			if ($i != $pos)
				$list[] = & $this->list[$i];
		$this->list = & $list;
	} // end func removePos
} // end class PlayList