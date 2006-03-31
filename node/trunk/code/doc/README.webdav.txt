StreamOnTheFly and WebDAV
Instructions for node administators


IMPORTANT SECURITY INFORMATION

At the moment, the .htaccess files which control the user authentication are created by the
time when a NEW user directory is created – concretely when a user first accesses his
“Editor’s Console” on the node.
Thus, existent user directories will preliminary be accessible via WebDAV without user
verification!
The WebDAV configuration as described here has successfully been tested on Windows
using WebDrive (http://www.webdrive.com). Unfortunately, I have not got any experience
with other system configurations.
Therefore, I would classify the current status of the WebDAV link as UNSTABLE.
As soon as everything is clarified, a script should be written to implement the WebDAV 
authentication into existent user directories.


INTRODUCTION

WebDAV is a comfortable alternative for node users to upload content onto the node.
It is directly integrated into Mac OS X systems and most LINUX distributions and – with the
help of an appropriate client – can also be used under Windows.
How to get WebDAV running on a SOTF node

1. First, download the WebDAV Apache Module from http://www.webdav.org/mod_dav/
and install it. Help can be found at http://www.webdav.org/mod_dav/install.html.
On Debian, you can easily get the whole package using apt-get:
apt-get install libapache-mod-dav

2. Create an DAVLockDB file. This file is used for storing information on file locks and
must be writable for the user under which Apache is running. If you are not sure, you
can find the required user name in the web server configuration.

3. Add the following lines into your Apache config file. Usually, it can be found under
/etc/apache/httpd.conf.

##################################################
DAVLockDB /path/to/the/DAVLockDB/file
Alias /webdav /path/to/the/node’s/users/directory
<Location /webdav>
DAV on
Order allow,deny
Allow from all
AllowOverride All
</Location>
<Directory / path/to/the/node’s/users/directory >
Order allow,deny
Allow from all
AllowOverride All
</Directory>
##################################################

After having done the changes, don’t forget to restart the web server.

4. That’s all. The user directories are now accessible via WebDAV under the following
address: www.node-server.url/webdav/username

The user will be asked to enter his SOTF node password after the connection has
been established.


Written on 05-09-28 by
-----------------------------------------------
Martin Schmidt | ptmschmidt@fh-stpoelten.ac.at
-----------------------------------------------