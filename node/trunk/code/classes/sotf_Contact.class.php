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

	/** static */
	function isNameInUse($name) {
		global $db, $config;
		// TODO!!
	}

	function create($name) {
		$id = $this->findByNameLocal($name);
		if($id) {
			debug("Create contact", "Failed, name in use");
			return false;
		}
		$this->data['name'] = $name;
		return parent::create();
	}

	function isLocal() {
		global $db, $config;
		$n = $db->getOne("SELECT node_id FROM sotf_node_objects WHERE id = '$this->id'");
		return ($n == $config['nodeId']);
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
		$res = $db->getAll("SELECT c.id AS id, c.name AS name FROM sotf_contacts c, sotf_node_objects n WHERE c.id = n.id AND n.node_id='" . $config['nodeId']."' ORDER BY name");
		if(DB::isError($res))
			raiseError($res);
		return $res;
	}

	function countProgrammes() {
	global $db, $repository;

		return $db->getOne("SELECT count(p.id) FROM sotf_contacts c, sotf_object_roles r, sotf_programmes p WHERE c.id = '$this->id' AND c.id=r.contact_id AND r.object_id = p.id");
	}

	function references() {
		global $db;
		return $db->getAll("SELECT r.object_id, r.role_id FROM sotf_contacts c, sotf_object_roles r WHERE c.id = '$this->id' AND c.id=r.contact_id");
	}

	function listProgrammes($start, $hitsPerPage) {
		global $db;

		$sql = "SELECT p.*, r.role_id FROM sotf_contacts c, sotf_object_roles r, sotf_programmes p WHERE c.id = '$this->id' AND c.id=r.contact_id AND r.object_id = p.id";
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