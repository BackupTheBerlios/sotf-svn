There are the following general permissions:

- OWNER
- CHANGE
- CREATE
- DELETE
- AUTHORIZE

OWNER means all the permissions (including those that are introduced later)

These rights has the following meaning in different contexts:

on programme level:
-------------------

- change metadata, icon, files, publish/unpublish, set streaming rights (CHANGE)
- authorize persons (AUTHORIZE)
- delete (DELETE)

Inherited rights:
- CHANGE, DELETE, AUTHORIZE, OWNER from station level
- CHANGE, DELETE, AUTHORIZE, OWNER from series level

on contact records:
-------------------

- change metadata and icon (CHANGE)
- authorize persons (AUTHORIZE)
- delete (DELETE)

Inherited rights:
- CHANGE, DELETE, AUTHORIZE, OWNER from station level

on series level:
----------------

- add programmes (CREATE)
- change series general data (CHANGE)
- authorize persons (AUTHORIZE)
- delete programmes (DELETE)

Inherited rights:
- CREATE, CHANGE, DELETE, AUTHORIZE, OWNER from station level

on station level:
-----------------

- add programmes (CREATE)
- create series (CHANGE)
- change station general data (CHANGE)
- delete programmes (DELETE)
- delete series (DELETE)
- authorize persons (AUTHORIZE)

on node level:
--------------

general read & browse access

normally everyone can read anything published.
Unpublished materials can only be seen/read by those who have CHANGE permission on
that programme

node manager

- create stations (CREATE)
- delete stations (DELETE)
- authorize and deauthorize station managers (AUTHORIZE)
- change node configuration (CHANGE)

on the level of controlled vocabularies:
----------------------------------------

- add new items into a given topic tree, or roles and genres list (CREATE)
- add/change translations for topics, roles, genres (CHANGE)
