<?php
require_once("$classdir/rpc_Utils.class.php");

class sotf_Neighbour extends sotf_Object {

  var $tablename = 'sotf_neighbours';

  function sotf_Neighbour($id='', $data='') {
    $this->sotf_Object('sotf_neighbours', $id, $data);
  }

	/** 
	 * @method static getById
	 */
	function getById($nodeId) {
		global $db;
		$data = $db->getRow("SELECT * FROM sotf_neighbours WHERE node_id = '$nodeId'");
    if(!$data) {
			logError('no such neighbour: $nodeId');
      return null;
    }
		return new sotf_Node($data['id'], $data);
	}

  /** returns a list of all such objects: can be slow!!
   * @method static listAll
   */
  function getAll() {
    global $db;
    $sql = "SELECT * FROM sotf_neighbours ORDER BY id";
    $res = $db->getAll($sql);
    if(DB::isError($res))
      raiseError($res);
    $slist = array();
    foreach($res as $st) {
      $slist[] = new sotf_Neighbour($st['id'], $st);
    }
    return $slist;
  }

  function sync() {
    $remoteNode = sotf_Node::getNodeById($this->get('node_id'));
    $localNode = sotf_Node::getLocalNode();
    // collect local data to send
    $objs = sotf_NodeObject::getModifiedObjects($this->get('last_outgoing'));
    $rpc = new rpc_Utils;
    $response = $rpc->call($remoteNode->get('url') . '/xmlrpcServer.php', 'sync', $objs);
    // save received data

    // save last_sync

  }


}

?>
