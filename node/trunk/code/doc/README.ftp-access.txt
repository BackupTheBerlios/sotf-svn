
How to provide FTP access for users of the StreamOnTheFly node?
---------------------------------------------------------------

Here we give an example using proftpd, but other FTP servers might as
well be able to do the job.

1. You need a proftpd with some modules compiled in. Especially you
will need mod_sql and mod_sql_postgres. mod_quota can be useful.  You
can check this with proftpd -l. An example output from a working
setup:

  mod_core.c
  mod_auth.c
  mod_xfer.c
  mod_site.c
  mod_ls.c
  mod_unixpw.c
  mod_log.c
  mod_tls.c
  mod_sql.c
  mod_sql_postgres.c
  mod_ratio.c
  mod_quota.c
  mod_pam.c
  mod_readme.c

2. Self-admin tool (SADM) will provide sql-based authentication for
proftpd. By default, the view ftp_auth does this. However, if you find
the this view is empty, or its contents are improper, you can tweak
ftp_auth like this:

CREATE VIEW "ftp_auth" AS SELECT authenticate.username, 
'www-data' AS groupname, 33000 AS uid, 33 AS gid, authenticate.passwd, 
'<path-to-user-dirs>' || authenticate.username AS homedir, 0 AS count, 
'/bin/sh' AS shell FROM authenticate;

3. Now you have to configure proftpd. The essential part is to tell
how SQL is used for authentication:

SQLConnectInfo      sadm@127.0.0.1 <user> <password>
SQLAuthTypes        Plaintext
SQLAuthenticate     users*
SQLUserInfo         ftp_auth username passwd NULL NULL homedir shell
#SQLGroupInfo        ftp_groups groupname gid members
SQLDefaultGID       33
SQLDefaultUID       33
SQLMinID            30
#SQLDefaultHomedir   /home/sotf
SQLHomedirOnDemand  on

4. Finally, in www/config.inc.php of the node set the FTP access:

$config['userFTP'] = 'ftp://my.node.host/';

Each user will be jailed into his own dir, which is created on demand.



---------------------------------------------------------------------
Following is a sample config file for proftpd. 
No liabilities for being secure enough.
This file also shows how to setup user quotas.
---------------------------------------------------------------------

ServerName                      "Debian"
ServerType                      inetd
#ServerType                     standalone
DeferWelcome                    off

ShowSymlinks                    on
MultilineRFC2228                on
DefaultServer                   on
ShowSymlinks                    on
AllowOverwrite                  on

DefaultTransferMode             binary

TimeoutNoTransfer               600
TimeoutStalled                  600
TimeoutIdle                     1200

#DisplayLogin                    welcome.msg
#DisplayFirstChdir               .message
#LsDefaultOptions                "-l"

DenyFilter                      \*.*/
DenyFilter                  "\\.quota"

# Uncomment this if you are using NIS or LDAP to retrieve passwords:
#PersistentPasswd               off

# Port 21 is the standard FTP port.
Port                            21

# To prevent DoS attacks, set the maximum number of child processes
# to 30.  If you need to allow more than 30 concurrent connections
# at once, simply increase this value.  Note that this ONLY works
# in standalone mode, in inetd mode you should use an inetd server
# that allows you to limit maximum number of processes per service
# (such as xinetd)
MaxInstances                    30

# Set the user and group that the server normally runs at.
User                            nobody
Group                           www-data

# Normally, we want files to be overwriteable.
<Directory /*>
  AllowOverwrite                on
   <Limit SIZE>
       Deny All
   </Limit>
</Directory>

<Global>
     PassivePorts 65400 65800
#     CommandBufferSize           256
     AllowOverwrite              on
     DefaultRoot                 ~/
     DenyFilter                  "%"
     DirFakeUser                 on ~
     DirFakeGroup                on ~
#     DisplayConnect              /etc/ftplogin
#     DisplayLogin                /etc/welcome.msg
#     DisplayGoAway               /etc/ftpmsg.dead
#     IdentLookups                on
     ServerIdent                 on "FTP Service ready"
     Umask                       007
     UseFtpUsers                 off

</Global>

Quotas on
# quota in bytes
DefaultQuota 500000000
#QuotaType soft/hard   soft is what it is currently, hard would remove the file that violated the quota
QuotaType hard
#QuotaCalc on/off      <- on quotas are calculated on the fly if 1) no .quota file exists or 2) the quota would go negative
QuotaCalc on
#QuotaExempt uid,uid   <- list of users whose files don't count against a quota
QuotaBlockSize          1048576
QuotaBlockName          Mb

SQLConnectInfo      sadm@127.0.0.1 user password
SQLAuthTypes        Plaintext
SQLAuthenticate     users*
SQLUserInfo         ftp_auth username passwd NULL NULL homedir shell
#SQLGroupInfo        ftp_groups groupname gid members
SQLDefaultGID       33
SQLDefaultUID       33
SQLMinID            30
#SQLDefaultHomedir   /home/sotf
SQLHomedirOnDemand  on

#DefaultRoot /tmp

TransferLog /var/log/proftpd-transfer.log
