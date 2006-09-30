
How programme metadata is stored?

Each programme have a unique id (see ....).

The minimal data for a programme which is enough for various listings
and search results should be easy to access. This includes: title,
creator, dates, abstract, icon, rating, language, length, type, genre,
rights (?)

Otherwise there must be a possiblity to store arbitrary new metadata elements.

Basically we prepare for metadata elements from XBMF and SOMA.

There are the following repeatable elements in SOMA:

title.alternative
creator
creator.role (??)
subject
contributor
contributor.role
type (??)
format (??)
format.extent
language
relation.*
coverage.*
extendedInfo

Persons and organizations are described with contact records
(sotf_contact) which are reusable in several metadata records.


