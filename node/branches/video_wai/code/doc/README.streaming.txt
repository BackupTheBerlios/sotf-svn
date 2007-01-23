
There are several ways to set up streaming:

1. use code/contrib/iceplay
2. use ices/holyshout
3. use Tamburine via XML-RPC
4. use Tamburine via tbrcmd
5. use HTTP streaming

Only 3, 4 and 5 support remote listening from other node.

The best is to choose 5 (HTTP streaming). In this case you can stop
reading this file, you don't have to compile/install icecast and
Tamburine. Audio files will be listened through Apache HTTP
connections from the www/tmp directory. (You have to enable following
symlinks for this directory in Apache config.)

If you want to participate in a node network, you must choose HTTP streaming.

Solution 3. does not always work, try it on your site, and if it does not work,
choose 4, and set $config['tamburineCMD'] in your config.inc.php!

Tamburine (http://tamburine.dyne.org/)
---------------------------------------

Follow the install instructions on http://tamburine.dyne.org/. 
Additionally to that:

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
