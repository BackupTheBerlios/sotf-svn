-- -*- tab-width: 2; indent-tabs-mode: 1; -*-

--	 $Id$
--
-- Created for the StreamOnTheFly project (IST-2001-32226)
-- Authors: András Micsik, Máté Pataki, Tamás Déri 
--				at MTA SZTAKI DSD, http://dsd.sztaki.hu

CREATE TABLE "sotf_vars" (
-- global persistent server variables
"id" serial PRIMARY KEY, -- just an id
"name" varchar(32) UNIQUE,
"value" varchar(255) NOT NULL
);

CREATE TABLE "sotf_permissions" (
-- list of accepted permissions
"id" serial PRIMARY KEY,
"permission" varchar(20) UNIQUE NOT NULL
);

CREATE TABLE "sotf_users" (
-- users table, if you are not using a separate db for user management
	"id" serial PRIMARY KEY,
 	"username" varchar(50),
	"realname" varchar(100),
  "language" char(3),
  "email" varchar(100),
  "password" varchar(50) DEFAULT '' NOT NULL,
  "last_visit" timestamptz,
  "num_logins" int4 DEFAULT '0',
  "getmail" char(1) DEFAULT 'N' NOT NULL
);

CREATE VIEW "ftp_auth" AS SELECT 
	sotf_users.username, 
	'www-data' AS groupname, 
	33000 AS uid, 
	33 AS gid, 
	sotf_users.password AS passwd, 
	'__PATH_TO_USER_DIRS__' || sotf_users.username AS homedir, 
	0 AS count, 
	'/bin/sh' AS shell 
	FROM sotf_users;

CREATE TABLE "sotf_user_prefs" (
-- user preferences stored as serialized objects
"id" int PRIMARY KEY,					-- same as auth_id in sadm
"username" varchar(50) NOT NULL,
"email" varchar(100),			-- temporary solution
"feedback" bool DEFAULT 't'::bool,  -- if he wants emails forwarded to him
"prefs" text
);

CREATE TABLE "sotf_user_history" (
-- past actions of the user, may be used for collaborative filtering
"id" serial PRIMARY KEY, -- just an id
"user_id" int, -- cannot reference to sadm.authenticate(auth_id)
"action" varchar(30), -- type of action the user did with object
"object_id" varchar(12),
"when" timestamptz
);

CREATE TABLE "sotf_node_objects" (
-- basis of replication + generic node object properties
"id" varchar(12) PRIMARY KEY,
"last_change" timestamptz DEFAULT CURRENT_TIMESTAMP,
"change_stamp" int DEFAULT 0,
"arrived" timestamptz DEFAULT CURRENT_TIMESTAMP,
"node_id" int2 --- REFERENCES sotf_nodes(node_id)
---	"st_id" varchar(40), -- id used at the station management side
---	"comments" varchar(10) 
);

CREATE TABLE "sotf_object_status" (
-- data needed for replication mechanism 
"id" varchar(12),
"node_id" int2, --- REFERENCES sotf_nodes(node_id)
CONSTRAINT "sotf_object_status_uniq" UNIQUE ("id", "node_id")
);

CREATE SEQUENCE "sotf_blobs_seq";

CREATE TABLE "sotf_blobs" (
-- for storing icons, jingles, and other large binary objects
"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
"object_id" varchar(12) REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
"name" varchar(20), -- name/type of content, e.g. icon
"data" bytea, -- binary content
CONSTRAINT "sotf_blobs_uniq" UNIQUE ("object_id", "name")
);

CREATE SEQUENCE "sotf_nodes_seq";

CREATE TABLE "sotf_nodes" (
-- data about nodes in the network 
-- REPLICATED
"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
"node_id" int2 UNIQUE NOT NULL,				-- this id and name
"name" varchar(40) UNIQUE NOT NULL,			-- will be negotiated via e-mail within a node network
"description" text,
"url" varchar(255) NOT NULL,
"neighbours" varchar(255), -- 
"last_sync_in" timestamptz,							-- time of last sync 
"last_sync_out" timestamptz							-- time of last sync 
);

CREATE TABLE "sotf_neighbours" (
-- the neighbours of this node 
"id" serial PRIMARY KEY,	-- just an id
"node_id" int2, -- same as in sotf_nodes, except for pending nodes
"accept_incoming" bool DEFAULT 't'::bool,
"use_for_outgoing" bool DEFAULT 't'::bool,
"last_sync_in" timestamptz,
"last_sync_out" timestamptz,	-- 
"errors" int DEFAULT 0,
"success" int DEFAULT 0,
"pending_url" varchar(200),
CONSTRAINT "sotf_neighbours_uniq" UNIQUE ("node_id")
);

CREATE TABLE "sotf_user_permissions" (
-- a user may have a set of permissions on object or globally
"id" serial PRIMARY KEY, -- just an id
"user_id" int, -- cannot reference to sadm.authenticate(auth_id) 
"object_id" varchar(12), 
-- the object in which group permissions apply (can be object_id or string: node, topictree, etc.)
"permission_id" int REFERENCES sotf_permissions(id) ON DELETE CASCADE,
CONSTRAINT "sotf_user_permissions_uniq" UNIQUE ("user_id", "object_id", "permission_id")
);

CREATE SEQUENCE "sotf_stations_seq";

CREATE TABLE "sotf_stations" (
-- REPLICATED 
"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
"name" varchar(32) NOT NULL,
"description" text,
"url" varchar(100),										-- URL for radio station website, if any
"language" varchar(40),									-- 3-letter codes separeted by comma
"entry_date" date DEFAULT CURRENT_DATE
);

CREATE INDEX sotf_stations_name_index ON sotf_stations (name);

CREATE SEQUENCE "sotf_contacts_seq";

CREATE TABLE "sotf_contacts" (
-- this is a person or organization record
-- REPLICATED
"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
"station_id" varchar(12) REFERENCES sotf_stations(id) ON DELETE CASCADE,  -- responsible station
"name" varchar(100) NOT NULL,
"alias" varchar(100),
"acronym" varchar(30),
"intro" text,
"email" varchar(100),
"address" varchar(255),
"phone" varchar(50),
"cellphone" varchar(50),
"fax" varchar(50),
"url" varchar(255),
"feedback" bool DEFAULT 'f'::bool  -- if he wants comments forwarded to him
);

CREATE SEQUENCE "sotf_series_seq";

CREATE TABLE "sotf_series" (
-- REPLICATED 
"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
"station_id" varchar(12) NOT NULL,
"name" varchar(255) DEFAULT 'untitled series' NOT NULL,	-- 
"description" text,
"url" varchar(100),											-- URL for radio series website, if any
"language" varchar(40),										-- 3-letter codes separeted by comma
"entry_date" date DEFAULT CURRENT_DATE,
FOREIGN KEY("station_id") REFERENCES sotf_stations("id") ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_object_roles_seq";

CREATE TABLE "sotf_object_roles" (
-- points from stations/series/etc. to contact records for editors/artists/etc.
-- REPLICATED
"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
"object_id" varchar(12) NOT NULL,
"contact_id" varchar(12) NOT NULL,
"role_id" int2,	-- SOMA role (ref. to sotf_role_names)
CONSTRAINT "sotf_roles_uniq" UNIQUE ("object_id", "contact_id", "role_id"),
FOREIGN KEY("contact_id") REFERENCES sotf_contacts("id") ON DELETE CASCADE,
FOREIGN KEY("object_id") REFERENCES sotf_node_objects("id") ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_programmes_seq";

CREATE TABLE "sotf_programmes" (
-- used to store generic and searchable metadata about radio programmes 
-- REPLICATED
"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
"guid" varchar(76) UNIQUE NOT NULL,							-- globally unique id
"station_id" varchar(12) NOT NULL,										-- dc.publisher ??
"series_id" varchar(12),													-- this prog is part of series
"track" varchar(32) NOT NULL,									-- part of id: unique within station and entry_date
"foreign_id" varchar(120),										-- if the publisher has some id schema...
"title" varchar(255) DEFAULT 'untitled',					-- dc.title
"alternative_title" varchar(255),							-- may be known under a different title
"episode_title" varchar(255),									-- may be used if the show is part of a series
"episode_sequence" int4,										-- may be used if the show is in a series
"is_part_of" varchar(12),										-- pointer to embedding show using GUID
"keywords" text,													-- dc.subject (free keywords)
"abstract" text,													-- dc.description
"entry_date" date DEFAULT date('now'::text) NOT NULL,	-- dc.date.available
"production_date" date,											-- dc.date.created
"broadcast_date" timestamptz,											-- dc.date.issued
"modify_date" date,												-- dc.date.modified
"expiry_date" date DEFAULT (timestamptz(date('now'::text)) + '56 days'::"interval"),	-- when programme will be made unavailable
"type" varchar(50) DEFAULT 'sound',							-- DCMI type (audio/video/etc.)
"genre_id" int2,														-- SOMA genre (ref. to sotf_genres)
"length" int2,														-- dc.format.extent = duration in seconds
"language" varchar(30),											-- dc.language (3-letter codes separeted by comma)
"spatial_coverage" text,										-- dc.coverage.spatial
"temporal_coverage" date,										-- dc.coverage.temporal
"published" bool DEFAULT 'f'::bool,							-- unpublished items are not searchable nor browsable
FOREIGN KEY("station_id") REFERENCES sotf_stations("id") ON DELETE CASCADE,
FOREIGN KEY("series_id") REFERENCES sotf_series("id") ON DELETE CASCADE --??
);
-- TODO UNIQUE station_id+entry_date+track

CREATE INDEX prg_lang_idx ON sotf_programmes (language);	 -- 
-- TODO more indexes

CREATE SEQUENCE "sotf_rights_seq";

CREATE TABLE "sotf_rights" (
-- used to store the rights for a programme
-- REPLICATED
"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
"prog_id" varchar(12) NOT NULL,
"start_time" int4,	--starting second of the rightcontrolled part
"stop_time"	 int4,  -- ending second. Both may be empty. If so, the rights text is valid for the complete programme
"rights_text" text,
FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_extradata_seq";

CREATE TABLE "sotf_extradata" (
-- generic metadata storage used for external metadata which cannot be translated into our db
-- REPLICATED
"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
"prog_id" varchar(12) NOT NULL,
"element" varchar(40) NOT NULL,
"qualifier" varchar(40),
"scheme" varchar(50),
"language" varchar(10) NOT NULL,
"value" text,
CONSTRAINT "sotf_extradata_uniq" UNIQUE ("prog_id", "element", "qualifier", "scheme", "language", "value"),
FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_other_files_seq";

CREATE TABLE "sotf_other_files" (
-- permissions on associated audio and other files for a radio programme
-- REPLICATED
"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
"prog_id" varchar(12) NOT NULL,
"filename" varchar(255) NOT NULL,
"caption" varchar(255),
"mime_type" varchar(50),
"filesize" int,
"last_modified" timestamptz,
"public_access" bool DEFAULT 't'::bool,
CONSTRAINT "sotf_other_files_u" UNIQUE ("prog_id", "filename"),
FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_media_files_seq";

CREATE TABLE "sotf_media_files" (
-- permissions on associated audio and other files for a radio programme
-- REPLICATED
"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
"prog_id" varchar(12) NOT NULL,
"filename" varchar(255) NOT NULL,
"caption" varchar(255),
"filesize" int,
"last_modified" timestamptz,
"play_length" int,
"kbps" int2,		-- kilobit per second
"vbr" bool DEFAULT 'f'::bool,		-- variable bitrate
"type" varchar(10),		-- e.g. audio, video
"mime_type" varchar(50),
"format" varchar(70),	-- e.g. mp3,24kbps,44100hz,stereo
"stream_access" bool DEFAULT 't'::bool,	-- if users may view it as a stream
"download_access" bool DEFAULT 'f'::bool,	-- if users may download it
"main_content" bool DEFAULT 'f'::bool,		-- if this file is a variation of the main programme audio/video or sg. else
CONSTRAINT "sotf_media_files_u" UNIQUE ("prog_id", "filename"),
FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_links_seq";

CREATE TABLE "sotf_links" (
-- web links associated with a radio programme
-- REPLICATED
"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
"prog_id" varchar(12) NOT NULL,
"url" varchar(255) NOT NULL,
"caption" varchar(255),
"public_access" bool DEFAULT 't'::bool,
CONSTRAINT "sotf_links_u" UNIQUE ("prog_id", "url"),
FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_topic_tree_defs_seq";

CREATE TABLE "sotf_topic_tree_defs" (
-- defines each node in topic trees used for classifying programmes
-- REPLICATED
"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
"supertopic" varchar(12) DEFAULT '0',
"tree_id" int2,
"name" varchar(100)
);

CREATE SEQUENCE "sotf_topic_trees_seq";

CREATE TABLE "sotf_topic_trees" (
-- basic data about available topic trees
-- REPLICATED
"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
"tree_id" int2 UNIQUE NOT NULL,
"subtopic_of" varchar(12) REFERENCES sotf_topic_tree_defs(id),
"name" varchar(255), -- default non-localized name
"url" varchar(100), -- URL to help on or homepage of this topic tree
"languages" varchar(255) -- comma separated list of language codes in which tree is available
);

CREATE SEQUENCE "sotf_topics_seq";

CREATE TABLE "sotf_topics" (
-- defines the topic translations used for classifying programmes
-- REPLICATED
"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,	 -- node_id,table_id,tree_id,topic_id e.g.: 001to012
"topic_id" varchar(12) NOT NULL,
"language" varchar(10) NOT NULL,
"topic_name" varchar(255) NOT NULL,
"description" varchar(255),
"url" varchar(120),
CONSTRAINT "sotf_topics_u" UNIQUE ("topic_id", "language"),
FOREIGN KEY("topic_id") REFERENCES sotf_topic_tree_defs("id") ON DELETE CASCADE
);

CREATE TABLE "sotf_topics_counter" (
-- defines the number of programmes in the topic
"id" serial PRIMARY KEY,
"topic_id" varchar(12) NOT NULL,
"number" int2,
"total" int2
);

CREATE SEQUENCE "sotf_prog_topics_seq";

CREATE TABLE "sotf_prog_topics" (
-- defines the topics associated with a radio programme
-- REPLICATED
"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
"prog_id" varchar(12) NOT NULL,
"topic_id" varchar(12) NOT NULL,
CONSTRAINT "sotf_prog_topics_u" UNIQUE ("topic_id", "prog_id"),
-- this might cause loss of valuable data: FOREIGN KEY("topic_id") REFERENCES sotf_topic_tree_defs("id") ON DELETE CASCADE,
FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_genres_seq";

CREATE TABLE "sotf_genres" (
-- defines the accepted list of genres
-- REPLICATED
"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
"genre_id" int2 NOT NULL,
"language" varchar(10) NOT NULL,
"name" varchar(255) NOT NULL,
CONSTRAINT "sotf_genres_u" UNIQUE ("genre_id", "language")
);

CREATE SEQUENCE "sotf_roles_seq";

CREATE TABLE "sotf_roles" (
-- defines the roles
-- REPLICATED
"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
"role_id" int2 UNIQUE NOT NULL,
"creator" bool NOT NULL
);

CREATE SEQUENCE "sotf_role_names_seq";

CREATE TABLE "sotf_role_names" (
-- defines the accepted list of roles
-- REPLICATED
"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
"role_id" int2 REFERENCES sotf_roles(role_id) ON DELETE CASCADE,
"language" varchar(10) NOT NULL,
"name" varchar(255) NOT NULL,
CONSTRAINT "sotf_role_names_u" UNIQUE ("role_id", "language")
);

CREATE SEQUENCE "sotf_deletions_seq";

CREATE TABLE "sotf_deletions" (
-- remember and propagate deletions to other nodes
-- REPLICATED
"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
"del_id" varchar(12) UNIQUE NOT NULL -- REFERENCES sotf_node_objects(id)
);

CREATE TABLE "sotf_playlists" (
-- registered users may bookmark things
-- user_id + order_id should be unique, but please don't put a constraint on this!
"id" serial PRIMARY KEY, -- just an id
"prog_id" varchar(12) NOT NULL,
"user_id" int, -- cannot reference to sadm.authenticate(auth_id)
"order_id" int,
"type" VARCHAR(10), -- use unclear yet
CONSTRAINT "sotf_playlists_u" UNIQUE ("prog_id", "user_id"),
FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE
);

CREATE TABLE "sotf_ratings" (
-- individual ratings made by registered persons or anonym users
"id" serial PRIMARY KEY,										-- just an id
"prog_id" varchar(12) NOT NULL,											-- sotf programme id
"user_node_id" int2,												-- node from where user came
"user_id" int,														-- user who rated or NULL if anonymous
-- todo: delete ratings of a deleted user or not?
"rate" SMALLINT NOT NULL DEFAULT '0',
"host" varchar(100) NOT NULL,									-- host from where the rating arrived
"portal" varchar(255),											-- the portal URL from where rating arrived 
"entered" timestamptz NOT NULL DEFAULT '-infinity',		-- date when rating arrived
"auth_key" varchar(50),											-- anti-abuse thingie
"problem" varchar(50) default NULL,							-- if any suspicious thing occurred during rating
CONSTRAINT "sotf_ratings_uniq" UNIQUE ("prog_id", "entered", "auth_key"), -- this is not perfect
FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_prog_rating_seq";

CREATE TABLE "sotf_prog_rating" (
-- calculated overall rating for a programme is stored here 
-- REPLICATED
"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
"prog_id" varchar(12) NOT NULL,						-- id of programme rated
"rating_value" float,									-- value of rating
"nodes_only" float,										-- value calculated excluding ratings from portals
"alt_value" float,										-- rating calculated in an alternative way 
"rating_count" int DEFAULT 0,							-- total number of raters	
"rating_count_reg" int DEFAULT 0,					-- number of registered raters	
"rating_count_anon" int DEFAULT 0,					-- number of anonymous raters
"rating_sum_reg" int DEFAULT 0,						-- sum of ratings by registered raters	
"rating_sum_anon" int DEFAULT 0,						-- sum of ratings by anonymous raters
"detail" text,												-- may contain more detailed structured data on rating 
FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_prog_refs_seq";

CREATE TABLE "sotf_prog_refs" (
-- referencing portal URLs for a radio programme 
-- REPLICATED
"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
"prog_id" varchar(12) NOT NULL,							-- programme being referenced
"station_id" varchar(12) NOT NULL,
"url" varchar(255) NOT NULL,					-- URL of portal page referencing to the program
"portal_name" varchar(200),					-- name of portal
"portal_home" varchar(200),					-- homepage of portal
"start_date" timestamptz,						-- date when prog appeared on portal  
"end_date" timestamptz,							-- date when prog disappeared from portal
"visits" int,										-- number of visits
"listens" int,										-- number of listens initiated from the portal
"rating" float,									-- rating on the portal
"raters" int,										-- number of raters on the portal
"comments" int2 DEFAULT '0',					-- number of comments
"detail" text,										-- may contain more detailed structured data on rating
CONSTRAINT "sotf_refs_u" UNIQUE ("prog_id", "url"),
FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_prog_stats_seq";

CREATE TABLE "sotf_prog_stats" (
-- download and listen statistics for a radio programme 
-- REPLICATED
"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
"prog_id" varchar(12) NOT NULL,			-- programme being referenced
"station_id" varchar(12) NOT NULL,		-- station of programme
"listens" int DEFAULT '0',					-- number of listens
"downloads" int DEFAULT '0',				-- number of downloads
"visits" int DEFAULT '0',					-- number of times page has been visited
"unique_listens" int DEFAULT '0',					-- number of users who listened
"unique_downloads" int DEFAULT '0',				-- number of users who downloaded
"unique_visits" int DEFAULT '0',					-- number of users the page has been visited
"detail" text,									-- may contain more detailed structured data on rating
FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE,
FOREIGN KEY("station_id") REFERENCES sotf_stations("id") ON DELETE CASCADE
);

CREATE TABLE "sotf_stats" (
-- detailed download and listen statistics for a radio programme 
"id" serial PRIMARY KEY,
"prog_id" varchar(12) NOT NULL,
"station_id" varchar(12) NOT NULL,
"year" int2 NOT NULL,
"month" int2 NOT NULL,
"week" int2 NOT NULL,
"day" int2 NOT NULL,
"listens" int DEFAULT '0',					-- number of listens
"downloads" int DEFAULT '0',				-- number of downloads
"visits" int DEFAULT '0',					-- number of times page has been visited
"unique_listens" int DEFAULT '0',					-- number of users who listened until that time
"unique_downloads" int DEFAULT '0',				-- number of users who downloaded until that time
"unique_visits" int DEFAULT '0',					-- number of users the page has been visited until that time
CONSTRAINT "sotf_stats_u" UNIQUE ("prog_id", "month", "year", "day"),
FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE,
FOREIGN KEY("station_id") REFERENCES sotf_stations("id") ON DELETE CASCADE
);

CREATE TABLE "sotf_comments" (
-- comments for a radio programme 
"id" serial PRIMARY KEY,
"prog_id" varchar(12) REFERENCES sotf_programmes(id) ON DELETE CASCADE,		-- id of programme
"from_email" varchar(60),				-- e-mail from where comment arrived
"from_name" varchar(60),				-- user name-like thing
"entered" timestamptz,					-- when user entered the comment
"portal" varchar(255),					-- the portal URL where the comment was made
"comment_title" text,						-- subject of the comment
"comment_text" text,						-- full text of the comment
"sent" bool DEFAULT 'f'::bool		-- if this has been sent to authors
);

CREATE TABLE "sotf_to_forward" (
-- data to forward to another node 
-- host??
"id" serial PRIMARY KEY,
"node_id" int2,	-- id of node to forward to
"prog_id" varchar(12) REFERENCES sotf_programmes(id) ON DELETE CASCADE,		-- id of programme
"type" varchar(10),	-- type of data
"data" text,			-- data to be sent
"entered" timestamptz	-- date of object
);

CREATE TABLE "sotf_to_update" (
-- data to update	 
"id" serial PRIMARY KEY,
"tablename" varchar(40),	-- 
"row_id" varchar(12),				-- id within table (may be numeric or varchar(12)
CONSTRAINT "sotf_to_update_u" UNIQUE ("tablename", "id")
);

CREATE TABLE "sotf_unique_access" (
-- memory to calculate unique access to prg 
"id" serial PRIMARY KEY,
"prog_id" varchar(12) REFERENCES sotf_programmes(id) ON DELETE CASCADE,		-- id of programme
"sub_id" varchar(12),		-- id of file within programme
"ip" varchar(100),			-- host or IP
"auth_key" varchar(50),		-- anti-abuse thingie
"action" bit varying(6)				-- type of access
);

CREATE TABLE "sotf_user_progs" (
-- stores editor-specific private settings for programmes
"id" serial PRIMARY KEY,		-- just an id
"user_id"  int,					-- cannot reference to sadm.authenticate(auth_id)
"prog_id" varchar(12) REFERENCES sotf_programmes(id) ON DELETE CASCADE,		-- id of programme
"comments" text,					-- editor's private comments
"flags" varchar(20)				-- various flags (e.g. important, to-do)
);

CREATE TABLE "sotf_streams" (
-- list of started streams 
"id" serial PRIMARY KEY,		-- just an id
"pid" int,							-- process id of streamer
"user_id"  int,					-- identify
"auth_key" varchar(50),			-- another way to identify user
"prog_id" varchar(12),			-- id of programme
"file_id" varchar(12),			-- id of file being played (null if playlist)
"playlist" varchar(40),			-- name of playlist file
"url" varchar(200),				-- url of stream
"started" timestamp,				-- 
"length" int,						-- estimated length of playlist in seconds
"will_end_at" timestamp,		-- 
"host" varchar(50),				-- host receiving the stream
"flags" varchar(20)				-- various flags
);

CREATE SEQUENCE "sotf_portals_seq";

CREATE TABLE "sotf_portals" (
-- list of portals connected to this node 
-- REPLICATED
"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
"name" varchar(50) NOT NULL,				-- name of portal
"language" varchar(40),						-- 3-letter codes separeted by comma
"url" varchar(255) UNIQUE NOT NULL,		-- url of portal (identifies portal)
"page_impression" int,						-- number of downloads of starting page 
"reg_users" int2,								-- number of registered users
"last_access" timestamptz,
"last_update" timestamptz
);


CREATE TABLE "sotf_station_mappings" (
-- provides mapping between ids on station server and ids on node
"id" serial PRIMARY KEY,		-- just an id
"type" varchar(30), -- type of thing
"id_at_node" varchar(12) UNIQUE REFERENCES sotf_node_objects(id) ON DELETE CASCADE,		-- id of thing at node
"station" varchar(12) REFERENCES sotf_stations(id) ON DELETE CASCADE,		-- id of station at node for which this mapping applies
"id_at_station" varchar(20),	-- id of thing on station server
CONSTRAINT "sotf_station_mappings_uniq" UNIQUE ("id_at_station", "station", "type")
);

INSERT INTO "sotf_permissions" ("id", "permission") VALUES('1', 'admin');
SELECT nextval('sotf_permissions_id_seq');
INSERT INTO "sotf_permissions" ("id", "permission") VALUES('2', 'change');
SELECT nextval('sotf_permissions_id_seq');
-- INSERT INTO "sotf_permissions" ("id", "permission") VALUES('3', 'add_prog'); -- not used any more!
-- SELECT nextval('sotf_permissions_id_seq');
INSERT INTO "sotf_permissions" ("id", "permission") VALUES('4', 'create');
SELECT nextval('sotf_permissions_id_seq');
INSERT INTO "sotf_permissions" ("id", "permission") VALUES('5', 'delete');
SELECT nextval('sotf_permissions_id_seq');
INSERT INTO "sotf_permissions" ("id", "permission") VALUES('6', 'authorize');
SELECT nextval('sotf_permissions_id_seq');

-- collection of some useful sql statementsm no neeed to run these!

-- alter table sotf_stats rename to sotf_stats_old
-- insert into sotf_stats (prog_id, station_id, year, week, month, day, listens, downloads, visits) select prog_id, station_id, year, week, month, day, listens, downloads, visits from sotf_stats_old

-- select count(*) FROM (select distinct on (language, published) language, published from sotf_programmes) AS foo;
-- SELECT a FROM test WHERE SUBSTRING(a FROM 1 FOR 1)=B'1';

-- update sotf_programmes set language='deu' where language='de' 
-- update sotf_programmes set language='eng' where language='en' 
-- update sotf_programmes set language='hun' where language='hu' 
