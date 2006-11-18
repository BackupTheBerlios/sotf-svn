
With this branch you can:

- Define who can listen/download content on high and low levels (premium-payable and promoted/sponsored content)
- Create user groups and assign permissions to them
- Use user groups with assigned premium-payable content to offer payable programme channels and online trainings
- Add partners with links and promotions to the new partner site (Rev. 595)
- View detailed user statistics from every site/listener/download (Rev. 595): 
  --> for this you need to install the open source online counter "chCounter 3.1.1" (GPL licenced) 
      (C) 2006 by Christoph Bachner, webmaster@christoph-bachner.net
- And some more...

Install:

- you have to check out this branch:
svn+ssh://svn.berlios.de/svnroot/repos/sotf/node/branches/payable

- you have to merge back your config file and any local changes from the previous setup

- two new entries need to be added to the config file node/www/config.inc.php:
  $config['payableMode'] = true;
  $config['currency'] = 'EUR';

- then you have to run the relevant entries at the end of code/share/update.sql on your
database

- set the default for sotf_programmes.free_content as you wish, newly
created programmes will be free or protected based on
this:
ALTER TABLE sotf_programmes ALTER free_content SET DEFAULT 'f'::bool;

if you want to set all existing programs free, then
UPDATE sotf_programmes SET free_content='t';

Listeners can only be defined for protected programmes.

You may customize:

- code/templates/protected.htm: this page is shown when someone tries to
listen/download without permission. You can give your bank account here :-)

- code/templates/userData.htm: the extra things you ask from users at
registration. The corresponding SQL table sotf_user_data has to be changed
accordingly.

Both pages are monolingual, currently Smarty-based localizations are not used there.

Then you can start it:

- create groups under Admin/Groups
- associate users with groups under Admin/Users
- define some programs as non-free in Edit metadata, near Save button
- define listen rights for the non-free programmes 
  permissions can be set both for users and for groups

Maintenance:

- you may run cleanTmpFiles.php periodically to clean up temporary playlists and
symlinks. If you run cron.php it also does this, but less frequently.

- sotf_user_history keeps track of user clicks and downloads. It can be used
for statistics.
