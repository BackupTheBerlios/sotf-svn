<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-
// $Id: sotf_User.class.php 548 2006-04-03 16:21:28Z buddhafly $

/** 
* This is a class for basic handling of users. Preferences and
* playlists are handled in separate classes.
*
* @author Andras Micsik SZTAKI DSD micsik@sztaki.hu, Martin Schmidt ptmschmidt@fh-stpoelten.ac.at
*/

class sotf_Group extends sotf_Object {

  function sotf_Group($id='', $data='') {
          $this->sotf_Object('sotf_groups', $id, $data);
  }

  /** returns a list of all such objects: can be slow!!
   * @method static listAll
   */
  function listAll() {
          global $db;
          $sql = "SELECT g.*, count(u.id) as count FROM sotf_groups g LEFT JOIN sotf_user_groups u ON g.id=u.group_id GROUP BY g.name, g.id, g.comments ORDER BY g.name";
          $res = $db->getAll($sql);
          if(DB::isError($res))
                  raiseError($res);
          return $res;
  }

  /** 
   * @method static getById
   */
  function getById($gid) {
          global $db;
          $gid = sotf_Utils::magicQuotes($gid);
          $id = $db->getOne("SELECT id FROM sotf_groups WHERE id = '$gid'");
          if(DB::isError($id))
                  raiseError($id);
          if($id)
                  return new sotf_Group($id);
          else
                  return NULL;
  }

  /** 
   * @method static getByName
   */
  function getByName($name) {
          global $db;
          $name = sotf_Utils::magicQuotes($name);
          $id = $db->getOne("SELECT id FROM sotf_groups WHERE name = '$name'");
          if(DB::isError($id))
                  raiseError($id);
          if($id)
                  return new sotf_Group($id);
          else
                  return NULL;
  }


}

?>