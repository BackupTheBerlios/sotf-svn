<?php // -*- tab-width: 3; indent-tabs-mode: 1; -*-

/* 
 * $Id$
 *
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: Andr�s Micsik, M�t� Pataki, Tam�s D�ri 
 *				at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

class sotf_Metadata {

  /** The program id */
  var $prg;

  /** The xml output */
  var $xml;


  function sotf_Metadata($prg) {
    $this->prg = $prg;
  }

  function writeMetaTag($tag, $value, $lang='', $attr='') {
    if(preg_match("/^[ \t\r\n]*$/", $value)) {
      //debug("EMPTY VALUE");
      return;
    }
    if($lang)
      $langAttr = "xml:lang=\"$lang\"";
    $value = htmlspecialchars($value);
    $this->xml .= "\n<$tag $attr $langAttr>$value</$tag>";
  }

  function getXBMFMetadata() {
	 global $vocabularies;

	 $prg = $this->prg;

	 $langs = $prg->getLanguagesArray();
	 if(count($langs) > 1)
	   $lang = $langs[0];
	 else
	   $lang = '';

	 $this->xml = '<metadata
  xmlns="http://www.streamonthefly.org/"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:dcterms="http://purl.org/dc/terms/"
  xmlns:xbmf="http://www.streamonthefly.org/xbmf"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 >';

	 $this->writeMetaTag('dc:title', $prg->get('title'), $lang);
	 $this->writeMetaTag('dcterms:alternative', $prg->get('alternative_title'), $lang);
	 $this->writeMetaTag('dc:publisher', $prg->stationName); // TODO: station or StreamOnTheFly?
	 //$this->writeMetaTag('dcterms:isPartOf', $prg->stationName, $lang);
	 $series = $prg->getSeries();
	 if($series) {
	   $this->writeMetaTag('dcterms:isPartOf', $series->get('name'));
	 }

	 $this->writeMetaTag('dc:subject', $prg->get('keywords'), $lang); // TODO: split with ;
	 $this->writeMetaTag('dc:description', $prg->get('abstract'), $lang);

	 // contacts
	 $roles = $prg->getRoles('eng');
	 foreach($roles as $role) {
		if($role['creator'])
		  $this->writeMetaTag('dc:creator', $role['contact_data']['name']);
		else
		  $this->writeMetaTag('dc:contributor', $role['contact_data']['name']);
	 }

	 $this->writeMetaTag('dc:date', $prg->get('production_date'));
	 $this->writeMetaTag('dcterms:available', $prg->get('entry_date'));
	 $this->writeMetaTag('dcterms:issued', $prg->get('broadcast_date'));
	 $this->writeMetaTag('dcterms:modified', $prg->get('modify_date'));
	 $this->writeMetaTag('dcterms:valid', $prg->get('expiry_date'));

	 $this->writeMetaTag('dc:type', 'Sound','','xsi:type="dcterms:DCMIType"');
	 $this->writeMetaTag('dc:format', 'audio/mpeg','','xsi:type="dcterms:IMT"');
	 $this->writeMetaTag('dcterms:extent', $prg->get('length'));
	 $this->writeMetaTag('dcterms:medium', 'online');

	 if($langs) 
	   foreach($langs as $l)
	     $this->writeMetaTag('dc:language', $l, '', 'xsi:type="dcterms:ISO639-2"');

	 $this->writeMetaTag('dc:identifier', 'streamonthefly:' . $prg->get('id'));
	 $this->writeMetaTag('dc:identifier', 'http://radio.sztaki.hu/node/get.php/' . $prg->get('id'));

	 $this->writeMetaTag('dcterms:spatial', $prg->get('spatial_coverage'));
	 $this->writeMetaTag('dcterms:temporal', $prg->get('temporal_coverage'));

	 // XBMF extensions
	 $this->writeMetaTag('xbmf:station', $prg->stationName);
	 if($series) {
	   $this->xml .= "\n<xbmf:series>";
	   $this->writeMetaTag('xbmf:seriestitle', $series->get('name'));
	   $this->writeMetaTag('xbmf:seriestitle', $series->get('description'));
	   $this->xml .= "\n</xbmf:series>";
	 }
	 $this->writeMetaTag('xbmf:episodetitle', $prg->get('episodetitle'));
	 $this->writeMetaTag('xbmf:episodesequence', $prg->get('episodesequence'));

	 $this->writeMetaTag('xbmf:genre', $vocabularies->getGenreName($prg->get('genre_id'), 'eng'), 'eng');

	 $topics = $prg->getTopics('eng');
	 foreach($topics as $topic) {
		$this->writeMetaTag('xbmf:topic', $topic['name'], 'eng');
	 }

	 // rights
	 $rights = $prg->getAssociatedObjects('sotf_rights', 'start_time');
	 foreach($rights as $right) {
	   $this->xml .= "\n<xbmf:rights>";
	   $this->writeMetaTag('xbmf:rightstext', $right['rights_text']); // $lang?
	   $this->writeMetaTag('xbmf:startime', $right['start_time']);
	   $this->writeMetaTag('xbmf:endtime', $right['stop_time']);
	   $this->xml .= "\n</xbmf:rights>";
	 }

	 // contacts
	 foreach($roles as $role) {
		$this->xml .= "\n<xbmf:contributor>";
		$this->writeMetaTag('xbmf:role', $role['role_name'], 'eng');
		$this->writeMetaTag('xbmf:name', $role['contact_data']['name']);
		$this->writeMetaTag('xbmf:acronym', $role['contact_data']['acronym']);
		$this->writeMetaTag('xbmf:email', $role['contact_data']['email']);
		$this->writeMetaTag('xbmf:phone', $role['contact_data']['phone']);
		$this->writeMetaTag('xbmf:address', $role['contact_data']['address']);
		$this->writeMetaTag('xbmf:uri', $role['contact_data']['uri']);
		$this->writeMetaTag('xbmf:intro', $role['contact_data']['intro']); // lang??
		$this->xml .= "\n</xbmf:contributor>";
	 }

	 $this->xml .= "\n</metadata>";

	 return $this->xml;
  }

  /*
  function getXBMFMetadataOld() {
	 global $vocabularies;
	 $xml = domxml_new_xmldoc('1.0');
	 $xbmf = domxml_add_root($xml, 'xbmf');
	 $xbmf->new_child('type','audio');
	 $xbmf->new_child('title',$this->get('title'));
	 $xbmf->new_child('alternative',$this->get('alternative_title'));
	 $xbmf->new_child('episodetitle',$this->get('episode_title'));
	 $xbmf->new_child('episodesequence',$this->get('episode_sequence'));
	 $xbmf->new_child('identifier', $this->getURL());
	 $station = $xbmf->new_child('station', $this->stationName);
	 $station->set_attribute('id', $this->get('station_id'));
	 //$xbmf->new_child('stationid', $this->get('station_id'));
	 // series
	 $series = $this->getSeries();
	 if($series) {
		$se = $xbmf->new_child('series', NULL);
		$se->new_child('id', $series->get('id'));
		$se->new_child('title', $series->get('name'));
		$se->new_child('description', $series->get('description'));
	 }
	 $lang = $xbmf->new_child('language', $this->getLanguagesLocalized($this->get('language')));
	 $lang->set_attribute('codes',$this->get('language'));
	 $xbmf->new_child('length', $this->get('length'));
	 $xbmf->new_child('keywords', $this->get('keywords'));
	 $xbmf->new_child('description', $this->get('abstract'));
	 $xbmf->new_child('genre', $vocabularies->getGenreName($this->get('genre_id')));
	 $topics = $this->getTopics();
	 foreach($topics as $topic) {
		$xbmf->new_child('topic', $topic['name']);
	 }
	 $xbmf->new_child('spatial_coverage', $this->get('spatial_coverage'));
	 $nod = $xbmf->new_child('date', $this->get('temporal_coverage'));
	 $nod->set_attribute('type','covering');
	 $nod = $xbmf->new_child('date', $this->get('production_date'));
	 $nod->set_attribute('type','created');
	 $nod = $xbmf->new_child('date', $this->get('broadcast_date'));
	 $nod->set_attribute('type','issued');
	 $nod = $xbmf->new_child('date', $this->get('entry_date'));
	 $nod->set_attribute('type','available');
	 $nod = $xbmf->new_child('date', $this->get('modify_date'));
	 $nod->set_attribute('type','modified');
	 // rights
	 $rights = $this->getAssociatedObjects('sotf_rights', 'start_time');
	 foreach($rights as $right) {
		$ri = $xbmf->new_child('right', $right['rights_text']);
		if(!$right['start_time'] && !$right['stop_time'])
		  $ri->set_attribute('for', 'all');
		else {
		  $ri->set_attribute('from', $right['start_time']);
		  $ri->set_attribute('to', $right['stop_time']);
		}
	 }
	 // contacts
	 $roles = $this->getRoles();
	 foreach($roles as $role) {
		$ro = $xbmf->new_child('contributor', NULL);
		$ro->set_attribute('role', $role['role_name']);
		$ro->set_attribute('role_id', $role['role_id']);
		$entity = $ro->new_child('entity',null);	
		$entity->set_attribute('type','organisation');
		$entity_name = $entity->new_child('name',$role['contact_data']['name']);
		$entity_name->set_attribute('type','organizationname');
		$entity_acronym = $entity->new_child('name',$role['contact_data']['acronym']);
		$entity_acronym->set_attribute('type','organizationacronym');
		$entity->new_child('e-mail',$role['contact_data']['email']);
		$entity->new_child('address',$role['contact_data']['address']);
		$entity->new_child('uri', $role['contact_data']['url']);
		$entity->new_child('phone', $role['contact_data']['phone']);
		$entity->new_child('intro', $role['contact_data']['intro']);
	 }
	 // prepare xml
	 $xmltext = $xml->dumpmem();
	 if(!$xmltext) {
		logError("Error preparing XBMF XML");
		return NULL;
	 }
	 return $xmltext;
  }
*/

}
