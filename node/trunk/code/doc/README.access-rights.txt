There are the following general permissions:

- ADMIN
- CHANGE
- CREATE
- DELETE
- AUTHORIZE
- ADD_PROG

ADMIN means all the permissions (including those that are introduced later)

These rights has the following meaning in different contexts:

on programme level:
-------------------

- change (including publish/unpublish and streaming rights) (CHANGE)
- authorize persons (AUTHORIZE)
- delete (DELETE)

on contact records:
-------------------

- change (CHANGE)
- authorize persons (AUTHORIZE)
- delete (DELETE)

on series level:
----------------

- add programmes (ADD_PROG)
- change series general data (CHANGE)
- authorize persons (AUTHORIZE)
- delete programmes (DELETE)

on station level:
-----------------

- add programmes (ADD_PROG)
- create series (CREATE)
- change station general data (CHANGE)
- delete programmes (DELETE)
- delete series (DELETE)
- authorize persons (AUTHORIZE)

on node level:
--------------

general read & browse access

normally everyone can read anything published.
Unpublished materials can only be seen/read by those who are working on
that programme (and station managers also???)

node manager

- create stations (CREATE)
- delete stations (DELETE)
- authorize and deauthorize station managers (AUTHORIZE)
- change node configuration (CHANGE)

on the level of controlled vocabularies:
----------------------------------------

- add new items into a given topic tree, or roles and genres list (CREATE)
- add/change translations for topics, roles, genres (CHANGE)
