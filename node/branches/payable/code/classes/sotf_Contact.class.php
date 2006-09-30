<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-

/*	
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *					at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

define('ERROR_NAME_USED', 123);

class sotf_Contact extends sotf_ComplexNodeObject {		

	 /**
		 * Constructor: loads the object from database if ids are given
		 *
		 * @param string tablename name of SQL table to store
		 * @param string node node id
		 * @param string id id within node
	 */
	function sotf_Contact($id='', $data='') {
		$this->sotf_ComplexNodeObject('sotf_contacts', $id, $data);
	}

	function create($name, $stationId) {
	  global $config;
	  $id = $this->findByNameLocal($name);
	  if($id) {
		 debug("Create contact", "Failed, name in use");
		 return false;
	  }
	  $this->data['name'] = $name;
	  $this->data['station_id'] = $stationId;
	  return parent::create();
	}

	/** static */
	function isNameInUse($name) {
		global $db, $config;
		// TODO!!
	}

  /** private
	  Checks and creates subdirs if necessary.
	*/
  function checkDirs() {
	$station = & $this->getObject($this->get('station_id'));
	$dir = $station->getDir() . '/__contacts';
	if(!is_dir($dir)) {
	  debug("created contacts dir", $dir);
	  mkdir($dir, 0770);
	}
	$dir = $dir . '/' . $this->id;
	if(!is_dir($dir)) {
	  debug("created contact dir", $dir);
	  mkdir($dir, 0770);
	}
	return $dir;
  }

  function getDir() {
	 $station = & $this->getObject($this->get('station_id'));
	 $dir = $station->getDir() . '/__contacts/' . $this->id;
	 return $dir;
  }
  
  /** returns the directory where metadata/jingles/icons are stored */
  function getMetaDir() {
	 return $this->getDir();
  }

  /** static */
  function moveContactsFromStation($station) {
	 global $db, $config;
	 $ids = $db->getCol("SELECT id FROM sotf_contacts WHERE station_id = '$station->id'");
	 if(!empty($ids)) {
		foreach($ids as $id) {
		  $contact = & $this->getObject($id);
		  $stations1 = $db->getCol("SELECT o.id FROM sotf_object_roles r, sotf_stations o WHERE r.contact_id = '$contact->id' AND r.object_id=o.id");
		  $stations2 = $db->getCol("SELECT o.station_id FROM sotf_object_roles r, sotf_series o WHERE r.contact_id = '$contact->id' AND r.object_id=o.id");
		  $stations3 = $db->getCol("SELECT o.station_id FROM sotf_object_roles r, sotf_programmes o WHERE r.contact_id = '$contact->id' AND r.object_id=o.id");
		  $stations = array_unique(array_merge($stations1, $stations2, $stations3));
		  if(!empty($stations)) {
			 $moved = false;
			 foreach($stations as $st) {
				if($st==$station->id)
				  continue;
				$node = $db->getOne("SELECT node_id FROM sotf_node_objects WHERE id='$st'");
				//debug("
				if($node==$config['nodeId']) {
				  $contact->set("station_id", $st);
				  $contact->update();
				  $moved = true;
				  break;
				}
			 }
			 if(!$moved) {
				// TODO: here we have problem: the contact is used only on stations on remote nodes!
				logError("Could not move contact '" . $contact->get('name') . "' used at stations: " . join(" ",$stations) . " The contact is deleted, sorry.");
			 }
		  }
		}
	 }
  }

	/** static */
	function findByNameLocal($name) {
		global $db, $config;
		$name = sotf_Utils::magicQuotes($name);
		$id = $db->getOne("SELECT c.id FROM sotf_contacts c, sotf_node_objects n WHERE c.id = n.id AND n.node_id='" . $config['nodeId'] . "' AND c.name = '$name'");
		return $id;
	}

	/** static */
	function findByName($name) {
		global $db, $config;
		$name = sotf_Utils::magicQuotes($name);
		// first find the local contact, then any other...
		//$id = sotf_Contact::findByNameLocal($name);
		//if(!$id)
			$id = $db->getOne("SELECT id FROM sotf_contacts WHERE name='$name'");
		// what happens when there are 2 matches? returns first match...
		return $id;
	}

	/** static */
	function listLocalContactNames() {
		global $db, $config;
		$res = $db->getAssoc("SELECT c.id AS id, c.name AS name FROM sotf_contacts c, sotf_node_objects n WHERE c.id = n.id AND n.node_id='" . $config['nodeId']."' ORDER BY name");
		if(DB::isError($res))
			raiseError($res);
		return $res;
	}

	/** static */
	function listMyContactNames() {
		global $db, $config, $user;
		$res = $db->getAssoc("SELECT c.id AS id, c.name AS name FROM sotf_contacts c, sotf_user_permissions p WHERE c.id = p.object_id AND p.user_id='" . $user->id . "' ORDER BY name");
		if(DB::isError($res))
			raiseError($res);
		return $res;
	}

	/** static */
	function listObjectContactNames($object) {
		global $db, $config, $user;
		$class = get_class($object);
		if($class == 'sotf_programme') {
		  $station = $object->getStation();
		} elseif($class == 'sotf_series') {
		  $station = $object->getStation();
		} elseif($class == 'sotf_station') {
		  $station = &$object;
		} else {
		  return $db->getAssoc("SELECT c.id AS id, c.name AS name FROM sotf_contacts c, sotf_object_roles r WHERE c.id = r.contact_id AND r.object_id='" . $object->id . "' ORDER BY name");
		}
		
		$res1 = $db->getAssoc("SELECT c.id AS id, c.name AS name FROM sotf_contacts c, sotf_object_roles r WHERE c.id = r.contact_id AND r.object_id='" . $station->id . "' ORDER BY name");
		if(DB::isError($res1))
			raiseError($res1);
		$res2 = $db->getAssoc("SELECT c.id AS id, c.name AS name FROM sotf_contacts c, sotf_series s, sotf_object_roles r WHERE c.id = r.contact_id AND r.object_id=s.id AND s.station_id = '" . $station->id . "' ORDER BY name");
		if(DB::isError($res2))
			raiseError($res2);
		$res3 = $db->getAssoc("SELECT c.id AS id, c.name AS name FROM sotf_contacts c, sotf_programmes s, sotf_object_roles r WHERE c.id = r.contact_id AND r.object_id=s.id AND s.station_id = '" . $station->id . "' ORDER BY name");
		if(DB::isError($res3))
			raiseError($res3);
		$res = array_merge($res1, $res2, $res3);
		asort($res);
		return $res;
	}

	/** static */
	function listAllContactNames() {
		global $db, $config, $user;
		// TODO: a-b-c split
		$res = $db->getAssoc("SELECT c.id AS id, c.name AS name FROM sotf_contacts c ORDER BY name");
		if(DB::isError($res))
			raiseError($res);
		return $res;
	}

	/** static */
	function searchContactNames($pattern) {
		global $db, $config, $user;
		$pattern = sotf_Utils::magicQuotes($pattern);
		$res = $db->getAssoc("SELECT c.id AS id, c.name AS name FROM sotf_contacts c WHERE name ~* '$pattern' ORDER BY name");
		if(DB::isError($res))
			raiseError($res);
		return $res;
	}

	function countProgrammes() {
	global $db, $repository;

		return $db->getOne("SELECT count(distinct(p.id)) FROM sotf_contacts c, sotf_object_roles r, sotf_programmes p WHERE c.id = '$this->id' AND c.id=r.contact_id AND r.object_id = p.id");
	}

	function references() {
		global $db;
		return $db->getAll("SELECT r.object_id, r.role_id FROM sotf_contacts c, sotf_object_roles r WHERE c.id = '$this->id' AND c.id=r.contact_id");
	}

	function listProgrammes($start, $hitsPerPage) {
		global $db;

		// TODO: make it distinct, but also list roles (if multiple) ... r.role_id is not collected
		$sql = "SELECT distinct(p.*) FROM sotf_contacts c, sotf_object_roles r, sotf_programmes p WHERE c.id = '$this->id' AND c.id=r.contact_id AND r.object_id = p.id";
		$res = $db->limitQuery($sql, $start, $hitsPerPage);
		if(DB::isError($res))
			raiseError($res);
		while (DB_OK === $res->fetchInto($item)) {
			$list[] = $item;
		}
		return $list;
	}

}

?>