
Portals
-------

Portals collect the following statistics:

- listen on programmes (counter)
- visits of programme detail page
- ratings on programmes (anon and registered, count)

Portals forward stats data to node periodically:

- comment -> when submitted
- listen  -> when stream started (with from_portal
- ratings -> when submitted
- visits -> periodically (??)
- prg. appear/disappear on portal (immediately)

Portals receive overall rating (and stats if needed) from node.


Nodes
-----

Nodes collect local feedback and feedback from portals, and
periodically forward those to the 'home node' of the programme.

Nodes may show the following feedback/stats data:

- number of visits to the prg detail page (timely)
- number of listens and downloads (timely)

  ( UNIQUE VISITORS?  REGISTERED? 
   only with cookie/session may start listen or download, and listen or
   download cannot repeat too often..., user action recorded 
   prg-host pairs to calculate unique
  )

- ratings for the prg in node network (anon and registered, count)

- per portal appearance:

  - appear start/end
  - portal address
  - no. of comments
  - no. of visits, no. of listens, no. of downloads (??)
  - rating on that portal


Preferences:
------------

set e-mail forwarding for comments
