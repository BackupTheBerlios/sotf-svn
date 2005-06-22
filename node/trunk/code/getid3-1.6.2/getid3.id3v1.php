<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                ///
//            or http://www.getid3.org                        ///
/////////////////////////////////////////////////////////////////
//                                                             //
// getid3.id3v1.php - part of getID3()                         //
// See getid3.readme.txt for more details                      //
//                                                             //
/////////////////////////////////////////////////////////////////

function getID3v1Filepointer(&$fd, &$ThisFileInfo) {

	fseek($fd, -128, SEEK_END);
	$id3v1tag = fread($fd, 128);

	if (substr($id3v1tag, 0, 3) == 'TAG') {
		$ThisFileInfo['avdataend'] = $ThisFileInfo['filesize'] - 128;

		require_once(GETID3_INCLUDEPATH.'getid3.id3.php');

		$ThisFileInfo['id3v1']['title']   = trim(substr($id3v1tag,   3, 30));
		$ThisFileInfo['id3v1']['artist']  = trim(substr($id3v1tag,  33, 30));
		$ThisFileInfo['id3v1']['album']   = trim(substr($id3v1tag,  63, 30));
		$ThisFileInfo['id3v1']['year']    = trim(substr($id3v1tag,  93,  4));
		$ThisFileInfo['id3v1']['comment'] =      substr($id3v1tag,  97, 30); // can't remove nulls yet, track detection depends on them
		$ThisFileInfo['id3v1']['genreid'] =  ord(substr($id3v1tag, 127,  1));

		if ((substr($ThisFileInfo['id3v1']['comment'], 28, 1) === chr(0)) && (substr($ThisFileInfo['id3v1']['comment'], 29, 1) !== chr(0))) {
			$ThisFileInfo['id3v1']['track'] = ord(substr($ThisFileInfo['id3v1']['comment'], 29, 1));
			$ThisFileInfo['id3v1']['comment'] = substr($ThisFileInfo['id3v1']['comment'], 0, 28);
		}
		$ThisFileInfo['id3v1']['comment'] = trim($ThisFileInfo['id3v1']['comment']);
		$ThisFileInfo['id3v1']['genre'] = LookupGenre($ThisFileInfo['id3v1']['genreid']);


		$ThisFileInfo['tags'][] = 'id3v1';

		// Do not change fileformat if already set
		if (empty($ThisFileInfo['fileformat'])) {
			$ThisFileInfo['fileformat'] = 'id3';
		}

		// ID3v1 has lowest preference. We add if $ThisFileInfo[comments] is empty - this will override empty tags of higher preference, or add comments to root if not already present
		if (isset($ThisFileInfo['id3v1'])) {
			CopyFormatCommentsToRootComments($ThisFileInfo['id3v1'], $ThisFileInfo, true, false, false);
		}
	}
	return true;
}

?>