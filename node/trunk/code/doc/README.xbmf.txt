Suggestions:

the thing has an id, which has a local part, let's call it track name.

our raw metadata file is stored as <TRACK>.xml

SOMA file is stored as  <TRACK>-soma.xml

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
        IST_MAX_MULLER.xml
        IST_MAX_MULLER-soma.xml
        other <dir>
                max_muller_at_night.gif
                max_muller.com.lnk
                important.doc
        audio <dir>
                IST_MAX_MULLER_128_2_44100.mp3
                IST_MAX_MULLER_64_2_44100.ogg
                IST_MAX_MULLER_24_1_44100.mp3


Is this enough to be really general?
Do we need audio in several parts?
