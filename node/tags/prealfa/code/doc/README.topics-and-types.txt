
The node network has a set of predefined lists and trees for use in metadata:
- topics (e.g. politics/democracy, health/sleep )
- genres (e.g. comedy, interview, news)
- roles (e.g. author, composer, narrator)
- types (e.g. audio, video, text)

These are served from a central, predefined server in the network, the
so-called topic server via XML-RPC calls. The node ships with default
lists which are overriden by the first download from the topic server.
The topic server is periodically checked for updates. While the topic
server is down, the last update is used from cache.

Genres, roles and types are simple lists with translations:
The ids may be numeric or textual for better readability of db.

genre_lists_def = array (
                "en" => english_list_def,
                "de" => german_list_def
                ...
                )

genre_list_def = array(
        "comedy" => "commedia"
        )

Definition of topic trees:

topic_def = array(treeId, topicId)
A topic is given by the numeric id of topic tree and leaf within that
tree. Example: array(2, 23)

topic_tree_def = array(
        id => 2,
        names => array(
                en => "SOMA topics",
                hu => "SOMA temakorok"
                )
        1 => array(
                parent => 0,
                id => 1,
                names => array(
                        en => "Politics",
                        hu => "Politika"
                        )
                )
        2 => array(
                parent => 1,
                id => 2,
                names => array(
                        en => "Austrian elections",
                        hu => "Ausztriai valasztasok"
                        )
                )
        )

So each topic has a set of translations to languages, and a parent topic
(or 0 if it's a root topic). Even we can consider the name of the
topic tree as topic 0 to have a very generalized dataset.

The topic server will serve this PHP dataset through XML-RPC to the nodes.
There will be a single request supported: get all topic trees.

How the maintenance of the topic trees is done on the topic server is
a separate problem.

There will be a default topic server configured by default for sotf nodes.
Nodes will periodically get the topic trees, and cache the topic tree
definitions, so if the topic server is done, only the updates are not
propagated to the node.

