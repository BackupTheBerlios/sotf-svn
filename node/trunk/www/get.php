<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*- 

/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

require("init.inc.php");

//$smarty->assign("OKURL", $_SERVER['PHP_SELF'] . "?id=" . rawurlencode($id));
$id = sotf_Utils::getParameter('id');
if($id) {

  $db->begin();

  $smarty->assign('ID', $id);

  $prg = &$repository->getObject($id);
  if(!$prg)
	 raiseError("no_such_object");

  if(!$prg->getBool('published')) {
	 if(!hasPerm($prg->id, 'change')) {
		raiseError("not_published_yet");
		exit;
	 }
	 $smarty->assign("UNPUBLISHED", 1);
  }

  $page->setTitle($prg->get('title'));

  // general data
  $smarty->assign('PRG_DATA', $prg->getAllWithIcon());
  // station data
  $station = $prg->getStation();
  $smarty->assign('STATION_DATA', $station->getAllWithIcon());
  // series data
  $series = $prg->getSeries();
  if($series) {
    $smarty->assign('SERIES_DATA', $series->getAllWithIcon());
  }

  // roles and contacts
  $smarty->assign('ROLES', $prg->getRoles());

  // genre
  $smarty->assign('GENRE', $vocabularies->getGenreName($prg->get('genre_id')));

  // topics
  $smarty->assign('TOPICS', $prg->getTopics());

  // language
  $smarty->assign('LANGUAGE', $prg->getLanguagesLocalized());
  // rights sections
  $smarty->assign('RIGHTS', $prg->getAssociatedObjects('sotf_rights', 'start_time'));

  // audio files 
  $audioFiles = $prg->getAssociatedObjects('sotf_media_files', 'main_content DESC, filename');
  for($i=0; $i<count($audioFiles); $i++) {
    $audioFiles[$i] =  array_merge($audioFiles[$i], sotf_AudioFile::decodeFormatFilename($audioFiles[$i]['format']));
	 $audioFiles[$i]['playtime_string'] = strftime('%M:%S', $audioFiles[$i]['play_length']);
  }
  $smarty->assign('AUDIO_FILES', $audioFiles);

  // other files
  $otherFiles = $prg->getAssociatedObjects('sotf_other_files', 'filename');
  $smarty->assign('OTHER_FILES', $otherFiles);
  
  // links
  $smarty->assign('LINKS', $prg->getAssociatedObjects('sotf_links', 'caption'));

  // referencing portals
  $smarty->assign('REFS', $prg->getRefs());

  // statistics
  $smarty->assign('STATS', $prg->getStats());

  // add this visit to statistics
  $prg->addStat('', "visits");

  // rating
  $rating = new sotf_Rating();
  $smarty->assign('RATING', $rating->getInstantRating($id));

  // my rating?
  debug("r", $rating->getMyRating($id));
  $smarty->assign('MY_RATING', $rating->getMyRating($id));

  if ($page->loggedIn()) {
    // is in my playlist?
    $smarty->assign('inplaylist', sotf_UserPlaylist::contains($id));
  }
}

$db->commit();

if(sotf_Utils::getParameter('popup')) {
  $smarty->assign('POPUP', 1);
  $page->sendPopup();
} else {
  $page->send();
}

?>