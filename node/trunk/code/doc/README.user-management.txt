
In node/code/classes there are files which implement various types of user
managament: their names start with 'userdb_'.

- userdb_sadm.class.php implements the old SADM-based user management
- userdb_node.class.php implements an independent user mgmt within sotf node

By default an sotf install now uses its own database for user management,
no SADM is needed.

In config.inc.php you define:
- if you want external user database
- which class should handle user management
- the details for the user database connection

It looks like this:

// set this to false if you will use a separate database for user management
$config['selfUserDb'] = true;

// by default we don't use external database for user management
$config['userDbClass'] = 'userdb_node';
// uncomment this if you use the Self Admin Toolkit (SADM) for user management
//$config['userDbClass'] = 'userdb_sadm';

// the sql connection parameters for the user database
// only fill in if you specified selfUserDb = false!!
$config['userDbType'] = 'pgsql';              //pgsql or mysql
$config['userDbUser'] = 'user';
$config['userDbHost'] = 'host';
$config['userDbPort'] = '5432';
$config['userDbPasswd'] = '';
$config['userDbName'] = 'sadm';

Creating custom user management class:

- make a copy of an existing class, rename it (keep prefix userdb_)
- you have to implement 8 functions, mostly change SQL statements.
- set this class in config.inc.php

For external database connectors I suggest to use userdb_sadm as a starting point.
In this way you can connect sotf even with LDAP or PAM (in theory) :-).




