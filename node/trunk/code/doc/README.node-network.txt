

How to join a node network?
---------------------------

1. log in as admin
2. select admin from top menu bar
3. press "add neighbour node"
4. fill in, for example for AT2:
   node id: 666
   URL: http://forschung.fh-stpoelten.ac.at/SOTF/node/www
   Press Create
5. The same has to be done on the 'other end of the wire', so on AT2 node
in this example. (until that your connection will be refused)
6. Press 'Sync' for that neighbour node. It will take some time for the
first time. You will see problems, if any.
7. Run cron.php regularly (as you already should do this), it will take
care of synchronization.

If there are too many bugs, or the node becomes unusable, delete the
neighbour on the admin screen.


About the operation of the Node network
---------------------------------------

Andras Micsik, SZTAKI
10 Dec 2002


Main goals:

1. Keep metadata synchronized
2. Control of node network

Operation

Each node defines its so-called neighbours (with id and URL). The node
will exchange data only with its neighbours. In this way neighbours
provide authentication for nodes: you cannot connect to a node if your
node is not in the neighbour list of that node.

Data exchange is done via so-called sync requests. Each node should
periodically send sync requests to its neighbours.

Sync

A sync request contains all new data on the node since the last
sync. In return, the node will get all new data from its neighbour.

Each piece of data that is synced is provided with a timestamp of the
last change date. So, if a node gets outdated data from a neighbour,
it is simply dropped.

The fact that a database record has been deleted are stored and
propagated similarly to other replicated data. In this way deletions
are done on remote nodes as well.

Extent of replication

Not replicated:
- user-specific data: passwords, access rights, playlists, individual ratings, etc.
- audio files and other associated files (except icons)

Anything else is replicated.

Joining the network

The administrator of the new node has to agree in a node id and node
name with the rest of the network. The administrator also selects the
node neighbours, and asks the admins of the neighbour nodes to put
this node on their neighbour list (for this they need the node id and
the url). After this the local admin also enters neighbour nodes into
the local neighbour list and issues the first sync request.

Method of replication

Objects with node id 0 are global objects, typically controlled
vocabularies.  Each replicated object has a change_stamp and a
time_stamp. Change_stamp is incremented in case of the object
changes. There is a sync_stamp which is incremented after each sync on
the node (both incoming and outgoing). The arrived_stamp of an object
is the sync_stamp of the node when the modified object was accepted
and stored during a sync. 
