
The node network has a set of predefined lists and trees for use in metadata:
- topics (e.g. politics/democracy, health/sleep )
- genres (e.g. comedy, interview, news)
- roles (e.g. author, composer, narrator)
- types (e.g. audio, video, text)

Type is unhandled currently, it is always 'audio'.

All other lists are propagated in the network the same way as audio metadata.
There is a so-called topic server for station management tool to
download actual versions of these lists via XML-RPC calls.
The node ships with default lists which are overriden when a node is connected to a node network.

The XML-RPC calls are defined as:

sotf.cv.listnames: lists available controlled vocabularies in format (type, name, language)

Example:
(topics, 1, 'en')
(roles, , 'en:de')
(genres, , 'en:de')

sotf.cv.get: retrieves selected vocabularies selected as (type, name, language)

Returns an array, each vocabulary item is one array in the big array.

Example:

Array
(
    [0] => Array
        (
            [id] => 000td1
            [name] => Development
            [supertopic] => 0
            [level] => 0
        )

    [1] => Array
        (
            [id] => 000td2
            [name] => Agriculture
            [supertopic] => 000td1
            [level] => 1
        )

    [2] => Array
        (
            [id] => 000td3
            [name] => Aid
            [supertopic] => 000td1
            [level] => 1
        )
