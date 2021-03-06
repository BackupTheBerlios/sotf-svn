
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

