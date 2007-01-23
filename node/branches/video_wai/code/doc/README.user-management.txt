
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


USING TYPO3 FOR USER MANAGEMENT:

- change config.inc.php to fit your typo3 database connection. 
  Important: for Port use 3306 (Standart mysql port)
  Select the typo3 class for userdb.
- in typo3 adminpanel go to phpmyadmin and create a new table:

# Table structure for table `fe_users_sotf`
CREATE TABLE fe_users_sotf (
  uid int(11) NOT NULL default '0',
  language varchar(6) default NULL,
  last_visit timestamp(12) NOT NULL,
  num_logins int(11) NOT NULL default '0',
  PRIMARY KEY  (uid)
) TYPE=MyISAM;

- that's it. now you should be able to register new user trough the node frontend.

Some known issues:

- if you create users in typo3, they don't get an entry in the table
'fe_users_sotf' automatically, only after the visit the page
"preferences (someuser)", then they get an entry in the table - or you
create an entry with defaults manually.

- if you delete a user in typo3, its entry in 'fe_users_sotf' DOESN'T
get deleted automatically - you have to delete it manually (via
phpMyAdmin in the Backend)
