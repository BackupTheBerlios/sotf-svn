<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-

/*
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: Andr�s Micsik, M�t� Pataki, Tam�s D�ri
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
         raiseError("no_such_object", $id);

  if(!$prg->getBool('published')) {
         if(!hasPerm($prg->id, 'change')) {
                raiseError("not_published_yet", $id);
                exit;
         }
         $smarty->assign("UNPUBLISHED", 1);
  }

  $page->setTitle($prg->get('title'));

  $subPage = 'getNormalContent';
  if(!$prg->getBool('free_content')) {
         $subPage = 'getProtectedContent';
  } elseif($prg->getBool('promoted')) {
         $subPage = 'getPromotedContent';
  }

  $smarty->assign("SUBPAGE", $subPage);

  // general data
  $prgData = $prg->getAll();
  $prgData['icon'] = sotf_Blob::cacheIcon($id);
  $smarty->assign('PRG_DATA', $prgData);
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
  $to = count($audioFiles);
  for($i=0; $i<$to; $i++) {
         if($prg->isLocal()) {
                // if local, we check if file disappeared in the meantime
                $path = $prg->getFilePath($audioFiles[$i]);
                if(!is_readable($path)) {
                  debug("DISAPPEARED FILE", $path);
                  unset($audioFiles[$i]);
                  continue;
                }
         }
    $audioFiles[$i] =  array_merge($audioFiles[$i], sotf_AudioFile::decodeFormatFilename($audioFiles[$i]['format']));
        $d = getdate($audioFiles[$i]['play_length']);
        $d['hours']--;
        $audioFiles[$i]['playtime_string'] = ($d['hours'] ? $d['hours'].':' : '') . sprintf('%02d',$d['minutes']) . ':' . sprintf('%02d',$d['seconds']);
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
  $myRating = $rating->getMyRating($id);
  debug("r", $myRating);
  $smarty->assign('MY_RATING', $myRating);

  if(nodeConfig('payableMode')) {
         $smarty->assign('CURRENCY', $config['currency']);
         if(!$prg->isFree()) {
                $smarty->assign('LISTEN_GROUPS', sotf_Group::listGroupsOfObject($id, 'listen'));
         }
  }

  if ($page->loggedIn()) {
    // is in my playlist?
    $smarty->assign('inplaylist', sotf_UserPlaylist::contains($id));
  }
}

$db->commit();


// online counter for statistics
if ($config['counterMode']) {
   $chCounter_status = 'active';
   $chCounter_visible = 0;
   $chCounter_page_title = 'Programm-Detailansicht - get.php';
   include($config['counterURL']);
}

if(sotf_Utils::getParameter('popup')) {
  $smarty->assign('POPUP', 1);
  $page->sendPopup();
} else {
  $page->send();
}

?>
