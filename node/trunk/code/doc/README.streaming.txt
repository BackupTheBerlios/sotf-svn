
There are several ways to set up streaming:

1. use code/contrib/iceplay
2. use ices/holyshout
3. use Tamburine via XML-RPC
4. use Tamburine via tbrcmd

Only 3 and 4 support remote listening from other node.
3. is not reliable.
So just choose 4, and set $config['tamburineCMD'] in your config.inc.php!


Tamburine (http://tamburine.dyne.org/)
---------------------------------------

Follow the install instructions on http://tamburine.dyne.org/. 
Additinoally to that:

Installation on Redhat:

Check if you have xmlrpc-c-config on your path. 
If not, install
xmlrpc-c-0.9.10-1.i386.rpm
xmlrpc-c-apps-0.9.10-1.i386.rpm
xmlrpc-c-devel-0.9.10-1.i386.rpm
Download from here: http://sourceforge.net/project/showfiles.php?group_id=16847

With certain versions of gcc you have to edit jnet.cpp and remove all
default values for parameters (e.g. recv(), peek()) to avoid compile
errors.

Insert this line at the beginning of cl_api.cpp:
#include <string.h>

You will still get many warnings like this:

This file includes at least one deprecated or antiquated
header. Please consider using one of the 32 headers found in section
17.4.1.2 of the C++ standard. Examples include substituting the <X>
header for the <X.h> header for C++ includes, or <sstream> instead of
the deprecated header <strstream.h>. To disable this warning use
-Wno-deprecated.

To run Tamburine as daemon you need to have a 
~/.tamburinerc:

[icecast]
desc = Try our radio archive network at http://sotf.dsd.sztaki.hu
url = http://sotf.dsd.sztaki.hu
name = StreamOnTheFly: your personalized radio!
public = 1
format = 1
protocol = 1
bps = 24
mount = live
pass = ...
port = 8000
server = sotf.dsd.sztaki.hu

You can start it with a command like this:

nohup /.../tamburine-0.3/tamburine >>/.../node/logs/play.log 2>&1 &

For each stream the total length is calculated and stream data are stored
in SQL. Using this database old streams are periodically cleared.
