<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-

/*
 * $Id$
 *
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri
 *					at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

class sotf_Node extends sotf_NodeObject {

	var $tablename = 'sotf_nodes';

	function sotf_Node($id='', $data='') {
		$this->sotf_NodeObject($this->tablename, $id, $data);
	}

	/** 
	 * @method static getNodeById
	 */
	function getNodeById($nodeId) {
		global $db;
		$id = $db->getOne("SELECT id FROM sotf_nodes WHERE node_id = '$nodeId'");
		if(DB::isError($id))
			raiseError($id);
		if($id)
			return new sotf_Node($id);
		else
			return NULL;
	}

	/** 
	 * @method static getLocalNode
	 */
	function getLocalNode() {
		global $db, $config;
		return sotf_Node::getNodeById($config['nodeId']);
	}

	/** static */
	function redirectToHomeNode($obj) {
	  // have to send user to home node of this programme
	  $node = sotf_Node::getNodeById($obj->getNodeId());
	  if(!$node) {
		 raiseError("Could not find home node for programme: " . $prg->id);
	  }
	  $url = $node->get('url') . getenv('QUERY_STRING');
	  $page->redirect($url);
	  exit;
	}

	/** returns a list of all such objects: can be slow!!
	 * @method static listAll
	 */
	function listAll() {
		global $db;
		$sql = "SELECT * FROM sotf_nodes ORDER BY name";
		$res = $db->getAll($sql);
		if(DB::isError($res))
			raiseError($res);
		$slist = array();
		foreach($res as $st) {
			$slist[] = new sotf_Node($st['id'], $st);
		}
		return $slist;
	}

	/** 
	 * @method static countAll
	 */
	function countAll() {
		global $db;
		return $db->getOne("SELECT count(*) FROM sotf_nodes");
	}

}

?>
