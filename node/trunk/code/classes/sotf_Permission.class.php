<?php
// -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id$

require_once("sotf_User.class.php");
require_once("sotf_Utils.class.php");

/**
* This is a class for handling permossions.
*
* @author	Tamas Kezdi SZTAKI DSD <tbyte@sztaki.hu>
* @package	StreamOnTheFly
* @version	0.1
*/
class sotf_Permission
{
	/**
	* Checks whether we have the requested permission
	*
	* @param	string	$permission	Permission type
	* @param	int	$station	ID of the station, if NULL it means the permission is global
	* @return	boolean	If we have the the requested permission returns true, else false
	* @use	$user
	*/
	function get($permission, $station = NULL)
	{
    /*
		global $user;

		if ($user)
			if ($station == NULL)
			{
				if ($user->permissionsGlobal)
					if (in_array($permission, $user->permissionsGlobal))
						return true;
			}
			else
				if ($user->permissions[$station])
					if (in_array($permission, $user->permissions[$station]))
						return true;
		return false;
    */
    return true;
	}

	/**
	* Adds the user to the global station manager group.
	*
	* @param	string	$username	Userid
	* @return	boolean	Returns true if succeeded
	* @use	$db
	*/
	function addStationManager($username)
	{
		global $db;

		$username = sotf_Utils::clean($username);
		$users = sotf_User::listUsers();
		if (in_array($username,$users))
		{
			$sm = $db->getCol("SELECT user_id FROM sotf_user_global_groups WHERE user_id='$username' AND (station='' OR station IS NULL) AND group_id='station_manager'");
			if (count($sm) == 0)
			{
				$db->query("INSERT INTO sotf_user_group (username, station, group_id) VALUES('$username', NULL, 'station_manager')");
				return true;
			}
		}
		return false;
	} // end func addStationManager

	/**
	* Removes the user from the global station manager group.
	*
	* @param	string	$username	Userid
	* @return	boolean	Returns true if succeeded
	* @todo	Error handling
	* @use	$db
	*/
	function delStationManager($username)
	{
		global $db;

		$username = sotf_Utils::clean($username);
		$db->query("DELETE FROM sotf_user_group WHERE username='$username' AND (station='' OR station IS NULL) AND group_id='station_manager'");
		return true;
	} // end func addStationManager

	/**
	* Returns the list of station managers.
	*
	* @return	array	List of station managers
	* @use	$db
	*/
	function getStationManagers()
	{
		global $db;
		return $db->getCol("SELECT username FROM sotf_user_group WHERE group_id='station_manager' AND (station='' OR station IS NULL) ORDER BY username");
	} // end func getStationManagers

	/**
	* List users and groups of a station.
	*
	* @param	string	$station	ID of the station
	* @return	array	List of users and groups
	* @use	$db
	*/
	function getUsersAndGroups($station)
	{
		global $db;

		return $db->getAll("SELECT username, group_id FROM sotf_user_group WHERE station='$station' ORDER BY username, group_id");
	} // end func getUsersAndGroups

	/**
	* List all existing groups.
	*
	* @return	array	Array of groups
	* @use	$db
	*/
	function getGroups()
	{
		global $db;

		return $db->getCol("SELECT DISTINCT group_id FROM sotf_group_permission");
	} // end func getGroups
	/**
	* Adds the user to a group in a station.
	*
	* @param	string	$username	Userid
	* @param	string	$group	ID of the group
	* @param	string	$station	Station
	* @return	boolean	Returns true if succeeded
	* @use	$db
	*/
	function addUserToGroup($username,$group,$station)
	{
		global $db;

		$username = sotf_Utils::clean($username);
		$group = sotf_Utils::clean($group);
		$station = sotf_Utils::clean($station);
		$users = sotf_User::listUsers();
		if (in_array($username,$users))
		{
			$user = $db->getOne("SELECT username FROM sotf_user_group WHERE username='$username' AND station='$station' AND group_id='$group'");
			if (!$user)
			{
				$db->query("INSERT INTO sotf_user_group (username, station, group_id) VALUES('$username', '$station', '$group')");
				return true;
			}
		}
		return false;
	} // end func addUserToGroup

	/**
	* Removes the user from a group in a station.
	*
	* @param	string	$username	Userid
	* @param	string	$group	ID of the group
	* @param	string	$station	Station
	* @return	boolean	Returns true if succeeded
	* @todo	Error handling
	* @use	$db
	*/
	function delUserFromGroup($username,$group,$station)
	{
		global $db;

		$username = sotf_Utils::clean($username);
		$group = sotf_Utils::clean($group);
		$station = sotf_Utils::clean($station);
		$db->query("DELETE FROM sotf_user_group WHERE username='$username' AND station='$station' AND group_id='$group'");
		return true;
	} // end func delUserFromGroup

	/**
	* Removes the user from station.
	*
	* @param	string	$username	Userid
	* @param	string	$station	Station
	* @return	boolean	Returns true if succeeded
	* @todo	Error handling
	* @use	$db
	*/
	function delUserFromStation($username,$station)
	{
		global $db;

		$username = sotf_Utils::clean($username);
		$station = sotf_Utils::clean($station);
		$db->query("DELETE FROM sotf_station_access WHERE user_id='$userid' AND station_id='$stationid'");
		return true;
	} // end func delUserFromGroup

  /** list stations for which the current user has the given right */
  function listStationsWithPermission($perm = 'upload') {
    global $user;
    while (list($stationname,$station) = each($user->permissions))
      {
        if (sotf_Permission::get($perm,$stationname))
          $stations[] = $stationname;
      }
    return $stations;
  }


} // end class sotf_Permission



?>