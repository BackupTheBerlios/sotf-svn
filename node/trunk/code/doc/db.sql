-- -*- tab-width: 3; indent-tabs-mode: 1; -*-
 
--  $Id$
--
-- Created for the StreamOnTheFly project (IST-2001-32226)
-- Authors: András Micsik, Máté Pataki, Tamás Déri 
--          at MTA SZTAKI DSD, http://dsd.sztaki.hu

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

CREATE TABLE "sotf_user_prefs" (
-- user preferences stored as serialized objects
	"id" int PRIMARY KEY,					-- same as auth_id in sadm
	"username" varchar(50) NOT NULL,
	"email" varchar(100),			-- temporary solution
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
   "change_stamp" int2 DEFAULT 0,
	"arrived" timestamptz DEFAULT CURRENT_TIMESTAMP,
	"arrived_stamp" int DEFAULT 0,
	"node_id" int2, --- REFERENCES sotf_nodes(node_id)
	"st_id" varchar(40), -- id used at the station management side
	"comments" varchar(10)
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
	"node_id" int2 UNIQUE NOT NULL, 				-- this id and name
	"name" varchar(40) UNIQUE NOT NULL,			-- will be negotiated via e-mail within a node network
	"description" text,
	"url" varchar(255) NOT NULL,
	"authorizer" int2,
	"last_sync" timestamptz
);

CREATE TABLE "sotf_neighbours" (
-- the neighbours of this node
	"id" serial PRIMARY KEY, 	-- just an id
	"node_id" int2, -- same as in sotf_nodes, except for pending nodes
	"accept_incoming" bool DEFAULT 't'::bool,
	"use_for_outgoing" bool DEFAULT 't'::bool,
	"last_sync" timestamptz,
	"last_sync_out" timestamptz,
	"sync_stamp" int DEFAULT 0,
	"errors" int,
	"success" int,
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

CREATE SEQUENCE "sotf_contacts_seq";

CREATE TABLE "sotf_contacts" (
-- this is a person or organization record
-- REPLICATED
	"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
	"name" varchar(100) NOT NULL,
	"alias" varchar(100),
	"acronym" varchar(30),
	"intro" text,
	"email" varchar(100),
	"address" varchar(255),
	"phone" varchar(20),
	"cellphone" varchar(20),
	"fax" varchar(20),
	"url" varchar(255)
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

CREATE SEQUENCE "sotf_stations_seq";

CREATE TABLE "sotf_stations" (
-- REPLICATED
	"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
	"name" varchar(32) UNIQUE NOT NULL,
	"description" text,
	"entry_date" date DEFAULT CURRENT_DATE
);

CREATE SEQUENCE "sotf_series_seq";

CREATE TABLE "sotf_series" (
-- REPLICATED
	"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
	"station_id" varchar(12) NOT NULL,
	"title" varchar(255) DEFAULT 'untitled' NOT NULL,
	"description" text,
	"entry_date" date DEFAULT CURRENT_DATE,
	FOREIGN KEY("station_id") REFERENCES sotf_stations("id") ON DELETE CASCADE
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
	"title" varchar(255) DEFAULT 'untitled',					-- dc.title
	"alternative_title" varchar(255), 							-- may be known under a different title
	"episode_title" varchar(255),									-- may be used if the show is part of a series
	"episode_sequence" int4,										-- may be used if the show is in a series
	"is_part_of" varchar(12),										-- pointer to embedding show using GUID
	"keywords" text, 													-- dc.subject (free keywords)
	"abstract" text,													-- dc.description
	"entry_date" date DEFAULT date('now'::text) NOT NULL,	-- dc.date.available
	"production_date" date,											-- dc.date.created
	"broadcast_date" date,											-- dc.date.issued
	"modify_date" date,												-- dc.date.modified
	"expiry_date" date DEFAULT (timestamptz(date('now'::text)) + '56 days'::"interval"),	-- when programme will be made unavailable
	"type" varchar(50) DEFAULT 'sound',							-- DCMI type (audio/video/etc.)
	"genre_id" int2,														-- SOMA genre (ref. to sotf_genres)
	"length" int2,														-- dc.format.extent = duration in seconds
	"language" varchar(10),											-- dc.language
	"spatial_coverage" text,										-- dc.coverage.spatial
	"temporal_coverage" date,										-- dc.coverage.temporal
	"published" bool DEFAULT 'f'::bool,							-- unpublished items are not searchable nor browsable
	FOREIGN KEY("station_id") REFERENCES sotf_stations("id") ON DELETE CASCADE,
	FOREIGN KEY("series_id") REFERENCES sotf_series("id") ON DELETE CASCADE --??
);

CREATE SEQUENCE "sotf_rights_seq";

CREATE TABLE "sotf_rights" (
-- used to store the rights for a programme
-- REPLICATED
	"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
	"prog_id" varchar(12) NOT NULL,
	"start_time" int4,   --starting second of the rightcontrolled part
	"stop_time"  int4,  -- ending second. Both may be empty. If so, the rights text is valid for the complete programme
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
	"public_access" bool DEFAULT 'f'::bool,
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
	"public_access" bool DEFAULT 'f'::bool,
	CONSTRAINT "sotf_links_u" UNIQUE ("prog_id", "url"),
	FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_topic_tree_defs_seq";

CREATE TABLE "sotf_topic_tree_defs" (
-- defines each node in topic trees used for classifying programmes
-- REPLICATED
	"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
	"supertopic" varchar(12) DEFAULT '0',
	"name" varchar(100)
);

CREATE SEQUENCE "sotf_topic_trees_seq";

CREATE TABLE "sotf_topic_trees" (
-- basic data about available topic trees
-- REPLICATED
	"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
	"tree_id" int2 UNIQUE NOT NULL,
	"subtree_id" varchar(12) REFERENCES sotf_topic_tree_defs(id),
	"name" varchar(100) UNIQUE NOT NULL,
	"description" text,
	"url" varchar(100)
);

CREATE SEQUENCE "sotf_topics_seq";

CREATE TABLE "sotf_topics" (
-- defines the topic translations used for classifying programmes
-- REPLICATED
	"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,  -- node_id,table_id,tree_id,topic_id e.g.: 001to012
	"topic_id" varchar(12) NOT NULL,
	"language" varchar(10) NOT NULL,
	"topic_name" varchar(255) NOT NULL,
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
	FOREIGN KEY("topic_id") REFERENCES sotf_topic_tree_defs("id") ON DELETE CASCADE,
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
	"id" serial PRIMARY KEY, 										-- just an id
	"prog_id" varchar(12) NOT NULL,											-- sotf programme id
	"user_node_id" int2,												-- node from where user came
	"user_id" int,														-- user who rated or NULL if anonymous
	-- todo: delete ratings of a deleted user or not?
	"rate" SMALLINT NOT NULL DEFAULT '0',
	"host" varchar(100) NOT NULL,									-- host from where the rating arrived
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
	"rating_count" int DEFAULT 0,							-- total number of raters	
	"rating_count_reg" int DEFAULT 0,					-- number of registered raters	
	"rating_count_anon" int DEFAULT 0,					-- number of anonymous raters
	"rating_sum_reg" int DEFAULT 0,						-- sum of ratings by registered raters	
	"rating_sum_anon" int DEFAULT 0,						-- sum of ratings by anonymous raters
	FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_refs_seq";

CREATE TABLE "sotf_refs" (
-- referencing portal URLs for a radio programme
-- REPLICATED
	"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
	"prog_id" varchar(12) NOT NULL,							-- programme being referenced
	"station_id" varchar(12) NOT NULL,
	"url" varchar(255) NOT NULL,					-- URL of portal article referencing to the program
	"comments" int2 DEFAULT '0',					-- number of comments
	CONSTRAINT "sotf_refs_u" UNIQUE ("prog_id", "url"),
	FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_stats_seq";

CREATE TABLE "sotf_stats" (
-- download and listen statistics for a radio programme
-- REPLICATED
	"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
	"prog_id" varchar(12) NOT NULL,
	"station_id" varchar(12) NOT NULL,
	"year" int2 NOT NULL,
	"month" int2 NOT NULL,
	"week" int2 NOT NULL,
	"day" int2 NOT NULL,
	"listens" int DEFAULT '0',					-- number of listens
	"downloads" int DEFAULT '0',				-- number of downloads
	"visits" int DEFAULT '0',					-- number of times page has been visited
	CONSTRAINT "sotf_stats_u" UNIQUE ("prog_id", "month", "year", "day"),
	FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE
);

CREATE TABLE "sotf_user_progs" (
-- stores editor-specific private settings for programmes
-- REPLICATED
	"id" serial PRIMARY KEY, 		-- just an id
	"user_id"  int, 				-- cannot reference to sadm.authenticate(auth_id)
	"prog_id" varchar(12) REFERENCES sotf_programmes(id) ON DELETE CASCADE,		-- id of programme
	"comments" text,					-- editor's private comments
	"flags" varchar(20)				-- various flags (e.g. important, to-do)
);

CREATE SEQUENCE "sotf_user_progs_seq";

INSERT INTO "sotf_permissions" ("id", "permission") VALUES('1', 'admin');
SELECT nextval('sotf_permissions_id_seq');
INSERT INTO "sotf_permissions" ("id", "permission") VALUES('2', 'change');
SELECT nextval('sotf_permissions_id_seq');
INSERT INTO "sotf_permissions" ("id", "permission") VALUES('3', 'add_prog');
SELECT nextval('sotf_permissions_id_seq');
INSERT INTO "sotf_permissions" ("id", "permission") VALUES('4', 'create');
SELECT nextval('sotf_permissions_id_seq');
INSERT INTO "sotf_permissions" ("id", "permission") VALUES('5', 'delete');
SELECT nextval('sotf_permissions_id_seq');
INSERT INTO "sotf_permissions" ("id", "permission") VALUES('6', 'authorize');
SELECT nextval('sotf_permissions_id_seq');


