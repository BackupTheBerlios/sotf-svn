<?php
require("init.inc.php");

//$smarty->assign("OKURL", $_SERVER['PHP_SELF'] . "?id=" . rawurlencode($id));
$id = sotf_Utils::getParameter('id');
if($id) {

  $smarty->assign('ID', $id);

  $prg = & new sotf_Programme($id);

  $page->setTitle($prg->get('title'));

  // general data
  $smarty->assign('PRG_DATA', $prg->getAll());
  // station data
  $station = $prg->getStation();
  $smarty->assign('STATION_DATA', $station->getAll());
  // series data
  $series = $prg->getSeries();
  if($series)
    $smarty->assign('SERIES_DATA', $series->getAll());

  // roles and contacts
  $smarty->assign('ROLES', $prg->getRoles());
  // genre

  // topics
  $smarty->assign('TOPICS', $prg->getTopics());

  $smarty->assign('GENRE', $repository->getGenreName($prg->get('genre_id')));
  // language
  $smarty->assign('LANGUAGE', $page->getlocalized($prg->get('language')));
  // rights sections
  $smarty->assign('RIGHTS', $prg->getAssociatedObjects('sotf_rights', 'start_time'));

  // audio files 
  $audioFiles = $prg->getAssociatedObjects('sotf_media_files', 'main_content DESC, filename');
  for($i=0; $i<count($audioFiles); $i++) {
    $audioFiles[$i] =  array_merge($audioFiles[$i], sotf_AudioFile::decodeFormatFilename($audioFiles[$i]['format']));
  }
  $smarty->assign('AUDIO_FILES', $audioFiles);

  // other files
  $otherFiles = $prg->getAssociatedObjects('sotf_other_files', 'filename');
  $smarty->assign('OTHER_FILES', $otherFiles);
  
  // links
  $smarty->assign('LINKS', $prg->getAssociatedObjects('sotf_links', 'caption'));


  //$smarty->assign('REFERENCES', $prg->getRefs());
  /* stats and refs are collected via xml-rpc ??
  if($localItem) {
    $smarty->assign($repo->getStats($idObj));
    $smarty->assign('REFS', $repo->getRefs($idObj));
  }
  */

}

if(sotf_Utils::getParameter('popup')) {
  $smarty->assign('POPUP', 1);
  $page->sendPopup();
} else {
  $page->send();
}

?>