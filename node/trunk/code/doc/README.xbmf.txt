Suggestions:

the thing has an id, which has a local part, let's call it track name.

our raw metadata file is stored as metadata.xml

SOMA file is stored as  metadata-soma.xml

there is a subdirectory called 'other' which may contain arbitrary
files/urls (urls are stored in separate files or in a single one?)

audio/video files can be arranged in separate subdirectories
or the type can be encoded into the filename. I think subdirs are better.
So if there is an 'audio' subdir, we can collect various audio formats of
the same thing from the files in there. If there is a 'video' subdir, it
is a video archive, and video content is in there.

The file name conventions within these subdirs are also important. It's
useful to encode audio/video properties into the filename. For audio
files:

<TRACK>-<BITRATE>-<CHANNELS>-<SAMPLERATE>.<FORMAT>

Example for an xbmf:

IST_MAX_MULLER <dir>
        metadata.xml
        metadata-soma.xml
	icon.png
        other <dir>
                max_muller_at_night.gif
                max_muller.com.lnk
                important.doc
        audio <dir>
                IST_MAX_MULLER_128kbps_2chn_44100Hz.mp3
                IST_MAX_MULLER_64kbps_2chn_44100Hz.ogg
                IST_MAX_MULLER_24kbps_1chn_44100Hz.mp3

Importing XBMF

XBMF can be imported manually under admin
...

...

// whether imported files are published by default
$config['publishxbmf'] = false;

Original XBMF DTD by Thomas Hassan:

<!ELEMENT address (#PCDATA |br )*>
<!ELEMENT alternative (#PCDATA )>
<!ELEMENT basetitle (#PCDATA )>
<!ELEMENT br EMPTY >
<!ELEMENT contributor (entity )>
<!ELEMENT creator (entity )>
<!ELEMENT date (#PCDATA |period )*>
<!ATTLIST date type NMTOKEN #REQUIRED >
<!ELEMENT description (#PCDATA )>
<!ELEMENT e-mail (#PCDATA )>
<!ELEMENT end (#PCDATA )>
<!ELEMENT entity (address |e-mail |logo |name |role |uri )*>
<!ATTLIST entity type NMTOKEN #REQUIRED >
<!ELEMENT episodesequence (#PCDATA )>
<!ELEMENT episodetitle (#PCDATA )>
<!ELEMENT extent (#PCDATA )>
<!ELEMENT format (resourcelocation,extent,medium )>
<!ATTLIST format type CDATA #REQUIRED >
<!ELEMENT identifier (#PCDATA )>
<!ELEMENT language (#PCDATA )>
<!ELEMENT logo (#PCDATA )>
<!ELEMENT medium (#PCDATA )>
<!ELEMENT name (#PCDATA )>
<!ATTLIST name type NMTOKEN #REQUIRED >
<!ELEMENT period (start,end )>
<!ATTLIST period name CDATA #REQUIRED >
<!ELEMENT publisher (entity )>
<!ELEMENT resourcelocation (#PCDATA )>
<!ELEMENT rights (#PCDATA )>
<!ELEMENT role (#PCDATA )>
<!ATTLIST role scheme CDATA #REQUIRED >
<!ELEMENT start (#PCDATA )>
<!ELEMENT subject (#PCDATA )>
<!ATTLIST subject scheme CDATA #REQUIRED >
<!ELEMENT title (basetitle,alternative,episodesequence,episodetitle )>
<!ELEMENT type (#PCDATA )>
<!ELEMENT uri (#PCDATA )>
<!ELEMENT Metadata (title,creator,subject+,description,publisher,contributor*,date+,type,
identifier,format+,language+,rights )>
<!ATTLIST Metadata version NMTOKEN #REQUIRED >



<?xml version="1.0"?>
<sotfPublish>
<title>A station called 0009</title>
<alternative></alternative>
<series><id>45</id><title>Radio Augustin</title><description>latest news from boulevard</description>
</series>
<stationid>1121</stationid><language>eng</language><rights>none</rights><genre>1</genre><topic>000td2</topic><description>A station called 0009</description><contributor></contributor><identifier>3316</identifier><creator><entity type="organisation"><name type="organizationname">Radio Tienes</name><name type="organizationacronym">RT</name><e-mail>radio@tienes.com</e-mail><address>Vienna is a big city</address><logo>http://www.pvl.at/logo.gif</logo><uri>http://www.pvl.at/</uri></entity></creator><publisher><entity type="organisation"><name type="organizationname">Radio Tienes</name><name type="organizationacronym">RT</name><e-mail>radio@tienes.com</e-mail><address>Vienna is a big city</address><logo>http://www.pvl.at/logo.gif</logo><uri>http://www.pvl.at/</uri></entity></publisher><date type="created">2003-06-06</date><date type="issued">2003-06-06</date><date type="available"></date><date type="modified"></date><owner><auth_id>28</auth_id><login>augustin</login><name>augustin</name><role>1</role></owner><publishedby><auth_id>28</auth_id><login>augustin</login><name>augustin</name><role>1</role></publishedby></sotfPublish> 