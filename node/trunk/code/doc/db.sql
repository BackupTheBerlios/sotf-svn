-- -*- tab-width: 3; indent-tabs-mode: 1; -*-
-- todo: define foreign keys, references, checks

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

INSERT INTO "sotf_permissions" ("id", "permission") VALUES('1', 'station_manager');
INSERT INTO "sotf_permissions" ("id", "permission") VALUES('2', 'read');
INSERT INTO "sotf_permissions" ("id", "permission") VALUES('3', 'write');

CREATE TABLE "sotf_groups" (
-- a group can have a set of permissions
	"id" serial PRIMARY KEY, -- just an id
	"name" varchar(30) UNIQUE NOT NULL
);

INSERT INTO "sotf_groups" ("id", "name") VALUES('1', 'station_manager');
INSERT INTO "sotf_groups" ("id", "name") VALUES('2', 'editor');
INSERT INTO "sotf_groups" ("id", "name") VALUES('3', 'admin');

CREATE TABLE "sotf_group_permission" (
-- a group can have a set of permissions
	"id" serial PRIMARY KEY, -- just an id
	"group_id" int REFERENCES sotf_groups("id") ON DELETE CASCADE,
	"permission_id" int REFERENCES sotf_permissions("id") ON DELETE CASCADE,
	CONSTRAINT "sotf_group_perm_u" UNIQUE ("group_id", "permission_id")
);

INSERT INTO "sotf_group_permission" ("group_id", "permission_id") VALUES(1, 1);
INSERT INTO "sotf_group_permission" ("group_id", "permission_id") VALUES(2, 2);
INSERT INTO "sotf_group_permission" ("group_id", "permission_id") VALUES(2, 3);
INSERT INTO "sotf_group_permission" ("group_id", "permission_id") VALUES(3, 1);
INSERT INTO "sotf_group_permission" ("group_id", "permission_id") VALUES(3, 2);
INSERT INTO "sotf_group_permission" ("group_id", "permission_id") VALUES(3, 3);

CREATE TABLE "sotf_user_prefs" (
-- user preferences stored as serialized objects
	"id" int PRIMARY KEY,					-- same as auth_id in sadm
	"username" varchar(50) NOT NULL,
	"e-mail" varchar(100),			-- temporary solution
	"prefs" text
);

INSERT INTO "sotf_user_prefs" ("id", "username") VALUES(1, 'admin');

CREATE TABLE "sotf_user_global_groups" (
-- a user may belong to global or station-local groups thus defining permissions 
	"id" serial PRIMARY KEY, -- just an id
	"user_id" int REFERENCES sotf_user_prefs(id) ON DELETE CASCADE,
	"group_id" int REFERENCES sotf_groups(id) ON DELETE CASCADE,
	CONSTRAINT "sotf_user_global_group_uniq" UNIQUE ("user_id", "group_id")
);

INSERT INTO "sotf_user_global_groups" ("user_id", "group_id") VALUES(1,1);

CREATE TABLE "sotf_user_history" (
-- past actions of the user, may be used for collaborative filtering
	"id" serial PRIMARY KEY, -- just an id
	"user_id" int REFERENCES sotf_user_prefs(id) ON DELETE CASCADE,
	"action" VARCHAR(30), -- type of action the user did with target
	"target_type" varchar(40),	-- e.g. station, programme, etc.
	"target_id" int,
	"when" datetime
);

CREATE TABLE "sotf_nodes" (
-- data about nodes in the network 
-- REPLICATED
	"id" int2 PRIMARY KEY, 							-- this id will be negotiated via e-mail within a node network
	"name" varchar(40) UNIQUE NOT NULL,
	"url" varchar(255) NOT NULL,
	"authorizer" varchar(40) NOT NULL,
	"ip" inet,
	"description" varchar(255),
	"up" bool NOT NULL,
	"last_sync" timestamptz,
	"last_change" timestamptz
);

CREATE TABLE "sotf_neighbours" (
-- the neighbours of this node
	"id" serial PRIMARY KEY, 	-- just an id
	"node_id" int2 REFERENCES sotf_nodes(id) ON DELETE CASCADE,
	"accept_incoming" bool DEFAULT 't'::bool,
	"use_for_outgoing" bool DEFAULT 't'::bool,
	"last_incoming" timestamptz,
	"last_outgoing" timestamptz,
	CONSTRAINT "sotf_neighbours_uniq" UNIQUE ("node_id")
);

CREATE SEQUENCE "sotf_contacts_seq";

CREATE TABLE "sotf_contacts" (
-- this is a person or organization record
-- REPLICATED
	"id" int DEFAULT nextval('sotf_contacts_seq') NOT NULL,
	"node_id" int2 REFERENCES sotf_nodes(id) ON DELETE CASCADE,
	"last_change" timestamptz DEFAULT CURRENT_TIMESTAMP,
-- end of basic stuff
	"name" varchar(100) NOT NULL,
	"alias" varchar(100),
	"acronym" varchar(30),
	"intro" text,
	"email" varchar(100),
	"address" varchar(255),
	"phone" varchar(20),
	"cellphone" varchar(20),
	"fax" varchar(20),
	"url" varchar(255),
	"icon" bytea,
	"jingle" bytea,
	CONSTRAINT "sotf_contacts_pkey" PRIMARY KEY ("id", "node_id")
);

CREATE TABLE "sotf_contact_access" (
-- who can edit this contact record
	"id" serial PRIMARY KEY, 	-- just an id
	"node_id" int2 REFERENCES sotf_nodes(id) ON DELETE CASCADE,
	"contact_id" int NOT NULL,
	"user_id" int REFERENCES sotf_user_prefs(id) ON DELETE CASCADE,
	"group_id" int REFERENCES sotf_groups(id) ON DELETE CASCADE,
	CONSTRAINT "sotf_contact_access_uniq" UNIQUE ("contact_id", "user_id", "group_id"),
	FOREIGN KEY("contact_id","node_id") REFERENCES sotf_contacts("id", "node_id") MATCH FULL ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_stations_seq";

CREATE TABLE "sotf_stations" (
-- REPLICATED
	"id" int DEFAULT nextval('sotf_stations_seq') NOT NULL,
	"node_id" int2 REFERENCES sotf_nodes(id) ON DELETE CASCADE,
	"last_change" timestamptz DEFAULT CURRENT_TIMESTAMP,
-- end of basic stuff
	"name" varchar(32) UNIQUE NOT NULL,
	"description" varchar(255),
	"icon" bytea,
	"jingle" bytea,
	CONSTRAINT "sotf_stations_pkey" PRIMARY KEY ("id", "node_id")
);

CREATE TABLE "sotf_station_access" (
-- a user may belong to global or station-local groups thus defining permissions 
-- for him as in table sotf_group_permission
	"id" serial PRIMARY KEY, -- just an id
	"node_id" int2 REFERENCES sotf_nodes(id) ON DELETE CASCADE,
	"station_id" int,
	"user_id" int REFERENCES sotf_user_prefs(id) ON DELETE CASCADE,
	"group_id" int REFERENCES sotf_groups(id) ON DELETE CASCADE,
	CONSTRAINT "sotf_station_access_uniq" UNIQUE ("user_id", "station_id", "group_id"),
	FOREIGN KEY("station_id","node_id") REFERENCES sotf_stations("id", "node_id") ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_station_roles_seq";

CREATE TABLE "sotf_station_roles" (
-- points to contact records for editors/artists/etc.
-- REPLICATED
	"id" int DEFAULT nextval('sotf_station_roles_seq') NOT NULL,
	"node_id" int2 REFERENCES sotf_nodes(id) ON DELETE CASCADE,
	"last_change" timestamptz DEFAULT CURRENT_TIMESTAMP,
-- end of basic stuff
	"station_id" int NOT NULL,
	"contact_id" int NOT NULL,
	"contact_node_id" int2 NOT NULL,
	"role" int2 NOT NULL,
	CONSTRAINT "sotf_station_roles_uniq" UNIQUE ("station_id", "contact_id", "role"),
	CONSTRAINT "sotf_station_roles_pkey" PRIMARY KEY ("id", "node_id"),
	FOREIGN KEY("contact_id","contact_node_id") REFERENCES sotf_contacts("id", "node_id") MATCH FULL ON DELETE CASCADE,
	FOREIGN KEY("station_id","node_id") REFERENCES sotf_stations("id", "node_id") MATCH FULL ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_series_seq";

CREATE TABLE "sotf_series" (
-- REPLICATED
	"id" int DEFAULT nextval('sotf_series_seq') NOT NULL,
	"node_id" int2 REFERENCES sotf_nodes(id) ON DELETE CASCADE,
	"last_change" timestamptz DEFAULT CURRENT_TIMESTAMP,
-- end of basic stuff
	"station_id" int NOT NULL,
	"title" varchar(255) DEFAULT 'untitled' NOT NULL,
	"description" text,
	"icon" bytea,
	"jingle" bytea,
	CONSTRAINT "sotf_series_pkey" PRIMARY KEY ("id", "node_id"),
	FOREIGN KEY("station_id","node_id") REFERENCES sotf_stations("id", "node_id") MATCH FULL ON DELETE CASCADE
);

CREATE TABLE "sotf_series_access" (
-- who can edit this series
	"id" serial PRIMARY KEY, 	-- just an id
	"node_id" int2 REFERENCES sotf_nodes(id) ON DELETE CASCADE,
	"series_id" int NOT NULL,
	"user_id" int REFERENCES sotf_user_prefs(id) ON DELETE CASCADE,
	"group_id" int REFERENCES sotf_groups(id) ON DELETE CASCADE,
	CONSTRAINT "sotf_series_access_uniq" UNIQUE ("series_id", "user_id", "group_id"),
	FOREIGN KEY("series_id","node_id") REFERENCES sotf_series("id", "node_id") MATCH FULL ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_series_roles_seq";

CREATE TABLE "sotf_series_roles" (
-- points to contact records for editors/artists/etc.
-- REPLICATED
	"id" int DEFAULT nextval('sotf_series_roles_seq') NOT NULL,
	"node_id" int2 REFERENCES sotf_nodes(id) ON DELETE CASCADE,
	"last_change" timestamptz DEFAULT CURRENT_TIMESTAMP,
-- end of basic stuff
	"series_id" int NOT NULL,
	"contact_id" int NOT NULL,
	"contact_node_id" int2 NOT NULL,
	"role" int2 NOT NULL,
	CONSTRAINT "sotf_series_roles_uniq" UNIQUE ("series_id", "contact_id", "contact_node_id", "node_id", "role"),
	CONSTRAINT "sotf_series_roles_pkey" PRIMARY KEY ("id", "node_id"),
	FOREIGN KEY("contact_id","contact_node_id") REFERENCES sotf_contacts("id", "node_id") MATCH FULL ON DELETE CASCADE,
	FOREIGN KEY("series_id","node_id") REFERENCES sotf_series("id", "node_id") MATCH FULL ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_programmes_seq";

CREATE TABLE "sotf_programmes" (
-- used to store generic and searchable metadata about radio programmes
-- REPLICATED
	"id" int DEFAULT nextval('sotf_programmes_seq') NOT NULL,
	"node_id" int2 REFERENCES sotf_nodes(id) ON DELETE CASCADE,
	"last_change" timestamptz DEFAULT CURRENT_TIMESTAMP,	-- needed for synchronization of nodes
-- end of basic stuff
	"guid" varchar(76) UNIQUE NOT NULL,							-- globally unique id
	"station_id" int NOT NULL,										-- dc.publisher ??
	"series_id" int,													-- this prog is part of series TODO: foreign key
	"track" varchar(32) NOT NULL,									-- part of id: unique within station and entry_date
	"title" varchar(255) DEFAULT 'untitled',					-- dc.title
	"alternative_title" varchar(255), 							-- may be known under a different title
	"episode_title" varchar(255),									-- may be used if the show is part of a series
	"episode_sequence" int4,										-- may be used if the show is in a series
	"keywords" text, 													-- dc.subject (free keywords)
	"abstract" text,													-- dc.description
	"entry_date" date DEFAULT date('now'::text) NOT NULL,	-- dc.date.available
	"production_date" date DEFAULT date('now'::text),		-- dc.date.created
	"broadcast_date" date,											-- dc.date.issued
	"modify_date" date,												-- dc.date.modified
	"expiry_date" date DEFAULT (timestamptz(date('now'::text)) + '56 days'::"interval"),	-- when programme will be made unavailable
	"type" varchar(50) DEFAULT 'sound',							-- DCMI type (audio/video/etc.)
	"genre" int2,														-- SOMA genre (ref. to sotf_genres)
	"length" int2,														-- dc.format.extent = duration in seconds
	"language" varchar(10),											-- dc.language
	"spatial_coverage" text,										-- dc.coverage.spatial
	"temporal_coverage" date,										-- dc.coverage.temporal
	"icon" bytea,														-- small image associated with prog
	"published" bool DEFAULT 'f'::bool,							-- unpublished items are not searchable nor browsable
	CONSTRAINT "sotf_programmes_pkey" PRIMARY KEY ("id", "node_id"),
	FOREIGN KEY("station_id","node_id") REFERENCES sotf_stations("id", "node_id") MATCH FULL ON DELETE CASCADE,
	FOREIGN KEY("series_id","node_id") REFERENCES sotf_series("id", "node_id") ON DELETE CASCADE
);

CREATE TABLE "sotf_prog_access" (
-- who can edit this programme
	"id" serial PRIMARY KEY, 	-- just an id
	"node_id" int2 REFERENCES sotf_nodes(id) ON DELETE CASCADE,
	"prog_id" int NOT NULL,
	"user_id" int REFERENCES sotf_user_prefs(id) ON DELETE CASCADE,
	"group_id" int REFERENCES sotf_groups(id) ON DELETE CASCADE,
	CONSTRAINT "sotf_prog_access_uniq" UNIQUE ("prog_id", "user_id", "group_id"),
	FOREIGN KEY("prog_id","node_id") REFERENCES sotf_programmes("id", "node_id") MATCH FULL ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_rights_seq";

CREATE TABLE "sotf_rights" (
-- used to store the rights for a programme
-- REPLICATED
	"id" int DEFAULT nextval('sotf_rights_seq') NOT NULL,
	"node_id" int2 REFERENCES sotf_nodes(id) ON DELETE CASCADE,
	"last_change" timestamptz DEFAULT CURRENT_TIMESTAMP,
-- end of basic stuff
	"prog_id" int NOT NULL,
	"start_time" int4 NOT NULL,   --starting second of the rightcontrolled part
	"stop_time"  int4 NOT NULL,  -- ending second. Both may be empty. If so, the rights text is valid for the complete programme
	"rights_text" text,
	CONSTRAINT "sotf_rights_pkey" PRIMARY KEY ("id", "node_id"),
	FOREIGN KEY("prog_id","node_id") REFERENCES sotf_programmes("id", "node_id") MATCH FULL ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_prog_roles_seq";

CREATE TABLE "sotf_prog_roles" (
-- points to contact records for editors/artists/etc.
-- REPLICATED
	"id" int DEFAULT nextval('sotf_prog_roles_seq') NOT NULL,
	"node_id" int2 REFERENCES sotf_nodes(id) ON DELETE CASCADE,
	"last_change" timestamptz DEFAULT CURRENT_TIMESTAMP,
-- end of basic stuff
	"prog_id" int NOT NULL,
	"contact_id" int NOT NULL,
	"contact_node_id" int2 NOT NULL,
	"role" int2,	-- SOMA role (ref. to sotf_roles)
	CONSTRAINT "sotf_prog_roles_uniq" UNIQUE ("prog_id", "contact_id", "role"),
	CONSTRAINT "sotf_prog_roles_pkey" PRIMARY KEY ("id", "node_id"),
	FOREIGN KEY("contact_id","contact_node_id") REFERENCES sotf_contacts("id", "node_id") ON DELETE CASCADE,
	FOREIGN KEY("prog_id","node_id") REFERENCES sotf_programmes("id", "node_id") MATCH FULL ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_extradata_seq";

CREATE TABLE "sotf_extradata" (
-- generic metadata storage used for external metadata which cannot be translated into our db
-- REPLICATED
	"id" int DEFAULT nextval('sotf_extradata_seq') NOT NULL,
	"node_id" int2 REFERENCES sotf_nodes(id) ON DELETE CASCADE,
	"last_change" timestamptz DEFAULT CURRENT_TIMESTAMP,
-- end of basic stuff
	"prog_id" int NOT NULL,
	"element" varchar(40) NOT NULL,
	"qualifier" varchar(40),
	"scheme" varchar(50),
	"language" varchar(10) NOT NULL,
	"value" text,
	CONSTRAINT "sotf_extradata_uniq" UNIQUE ("prog_id", "element", "qualifier", "scheme", "language", "value"),
	CONSTRAINT "sotf_extradata_pkey" PRIMARY KEY ("id", "node_id"),
	FOREIGN KEY("prog_id","node_id") REFERENCES sotf_programmes("id", "node_id") MATCH FULL ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_other_files_seq";

CREATE TABLE "sotf_other_files" (
-- permissions on associated audio and other files for a radio programme
-- REPLICATED
	"id" int DEFAULT nextval('sotf_other_files_seq') NOT NULL,
	"node_id" int2 REFERENCES sotf_nodes(id) ON DELETE CASCADE,
	"last_change" timestamptz DEFAULT CURRENT_TIMESTAMP,
-- end of basic stuff
	"prog_id" int NOT NULL,
	"filename" varchar(32) NOT NULL,
	"caption" varchar(255),
	"filesize" int,
	"last_modified" datetime,
	"public_access" bool DEFAULT 'f'::bool,
	CONSTRAINT "sotf_other_files_u" UNIQUE ("prog_id", "filename"),
	CONSTRAINT "sotf_other_files_pkey" PRIMARY KEY ("id", "node_id"),
	FOREIGN KEY("prog_id","node_id") REFERENCES sotf_programmes("id", "node_id") MATCH FULL ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_media_files_seq";

CREATE TABLE "sotf_media_files" (
-- permissions on associated audio and other files for a radio programme
-- REPLICATED
	"id" int DEFAULT nextval('sotf_media_files_seq') NOT NULL,
	"node_id" int2 REFERENCES sotf_nodes(id) ON DELETE CASCADE,
	"last_change" timestamptz DEFAULT CURRENT_TIMESTAMP,
-- end of basic stuff
	"prog_id" int NOT NULL,
	"filename" varchar(32) NOT NULL,
	"caption" varchar(255),
	"filesize" int,
	"last_modified" datetime,
	"play_length" int,
	"type" varchar(10),		-- e.g. audio, video
	"format" varchar(70),	-- e.g. mp3,24kbps,44100hz,stereo
	"stream_access" bool DEFAULT 't'::bool,	-- if users may view it as a stream
	"download_access" bool DEFAULT 'f'::bool,	-- if users may download it
	"main_content" bool DEFAULT 'f'::bool,		-- if this file is a variation of the main programme audio/video or sg. else
	CONSTRAINT "sotf_media_files_u" UNIQUE ("prog_id", "filename"),
	CONSTRAINT "sotf_media_files_pkey" PRIMARY KEY ("id", "node_id"),
	FOREIGN KEY("prog_id","node_id") REFERENCES sotf_programmes("id", "node_id") MATCH FULL ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_links_seq";

CREATE TABLE "sotf_links" (
-- web links associated with a radio programme
-- REPLICATED
	"id" int DEFAULT nextval('sotf_links_seq') NOT NULL,
	"node_id" int2 REFERENCES sotf_nodes(id) ON DELETE CASCADE,
	"last_change" timestamptz DEFAULT CURRENT_TIMESTAMP,
-- end of basic stuff
	"prog_id" int NOT NULL,
	"url" varchar(255) NOT NULL,
	"caption" varchar(255),
	CONSTRAINT "sotf_links_u" UNIQUE ("prog_id", "url"),
	CONSTRAINT "sotf_links_pkey" PRIMARY KEY ("id", "node_id"),
	FOREIGN KEY("prog_id","node_id") REFERENCES sotf_programmes("id", "node_id") MATCH FULL ON DELETE CASCADE
);

CREATE TABLE "sotf_topic_trees" (
-- defines the topic trees used for classifying programmes
	"id" serial,		-- not really needed...
	"tree_id" int2 NOT NULL,
	"topic_id" int NOT NULL,
	"supertopic" int DEFAULT '0',
	CONSTRAINT "sotf_topic_trees_pkey" PRIMARY KEY ("topic_id", "tree_id")
);

CREATE TABLE "sotf_topics" (
-- defines the topic translations used for classifying programmes
	"id" serial,		-- not really needed...
	"tree_id" int2 NOT NULL,
	"topic_id" int NOT NULL,
	"language" varchar(10) NOT NULL,
	"topic_name" varchar(255),
	CONSTRAINT "sotf_topics_u" UNIQUE ("topic_id", "tree_id", "language"),
	FOREIGN KEY("topic_id", "tree_id") REFERENCES sotf_topic_trees("topic_id", "tree_id") ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_prog_topics_seq";

CREATE TABLE "sotf_prog_topics" (
-- defines the topics associated with a radio programme
-- REPLICATED
	"id" int DEFAULT nextval('sotf_prog_topics_seq') NOT NULL,
	"node_id" int2 REFERENCES sotf_nodes(id) ON DELETE CASCADE,
	"last_change" timestamptz DEFAULT CURRENT_TIMESTAMP,
-- end of basic stuff
	"prog_id" int NOT NULL,
	"tree_id" int2 NOT NULL,
	"topic_id" int NOT NULL,
	CONSTRAINT "sotf_prog_topics_u" UNIQUE ("topic_id", "tree_id", "prog_id"),
	CONSTRAINT "sotf_prog_topics_pkey" PRIMARY KEY ("id", "node_id"),
	FOREIGN KEY("topic_id", "tree_id") REFERENCES sotf_topic_trees("topic_id", "tree_id") ON DELETE CASCADE,
	FOREIGN KEY("prog_id","node_id") REFERENCES sotf_programmes("id", "node_id") MATCH FULL ON DELETE CASCADE
);

CREATE TABLE "sotf_genres" (
-- defines the accepted list of genres
	"id" int2 NOT NULL,
	"language" varchar(10) NOT NULL,
	"name" varchar(255) NOT NULL,
	CONSTRAINT "sotf_genres_pkey" PRIMARY KEY ("id", "language")
);

CREATE TABLE "sotf_roles" (
-- defines the accepted list of roles
	"id" int2 NOT NULL,
	"language" varchar(10) NOT NULL,
	"name" varchar(255) NOT NULL,
	CONSTRAINT "sotf_roles_pkey" PRIMARY KEY ("id", "language")
);

CREATE SEQUENCE "sotf_deletions_seq";

CREATE TABLE "sotf_deletions" (
-- remember and propagate deletions to other nodes
-- deletions of many table rows are done via foreign keys!!
-- REPLICATED
	"id" int DEFAULT nextval('sotf_deletions_seq') NOT NULL,
	"node_id" int2 REFERENCES sotf_nodes(id) ON DELETE CASCADE,
	"last_change" timestamptz DEFAULT CURRENT_TIMESTAMP,  -- this here means time of deletion!!
-- end of basic stuff
	"table_name" varchar(10) NOT NULL,
	"del_id" varchar(100) NOT NULL,
	CONSTRAINT "sotf_del_pkey" PRIMARY KEY ("node_id","id"),
	CONSTRAINT "sotf_del_u" UNIQUE ("table_name","node_id", "del_id")
);

CREATE TABLE "sotf_playlists" (
-- registered users may bookmark things
	"id" serial PRIMARY KEY, -- just an id
	"prog_id" int NOT NULL,
	"node_id" int2 NOT NULL,
	"user_id" int REFERENCES sotf_user_prefs(id) ON DELETE CASCADE,
	"type" VARCHAR(10), -- use unclear yet
	CONSTRAINT "sotf_playlists_u" UNIQUE ("prog_id", "node_id", "user_id"),
	FOREIGN KEY("prog_id","node_id") REFERENCES sotf_programmes("id", "node_id") MATCH FULL ON DELETE CASCADE
);

CREATE TABLE "sotf_ratings" (
-- individual ratings made by registered persons or anonym users
	"id" serial PRIMARY KEY, 										-- just an id
	"node_id" int2 NOT NULL,										-- node of programme
	"prog_id" int NOT NULL,											-- sotf programme id
	"user_node_id" int2,												-- node from where user came
	"user_id" int,														-- user who rated or NULL if anonymous
	-- REFERENCES sotf_user_prefs(id) ON DELETE CASCADE
	-- todo: delete ratings of a deleted user or not?
	"rate" SMALLINT NOT NULL DEFAULT '0',
	"host" varchar(100) NOT NULL,									-- host from where the rating arrived
	"entered" datetime NOT NULL DEFAULT '-infinity',		-- date when rating arrived
	"auth_key" varchar(50),											-- anti-abuse thingie
	"problem" varchar(50) default NULL,							-- if any suspicious thing occurred during rating
	CONSTRAINT "sotf_ratings_uniq" UNIQUE ("node_id", "prog_id", "entered", "auth_key"), -- this is not perfect
	FOREIGN KEY("prog_id", "node_id") REFERENCES sotf_programmes("id", "node_id") MATCH FULL ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_prog_rating_seq";

CREATE TABLE "sotf_prog_rating" (
-- calculated overall rating for a programme is stored here
-- REPLICATED
	"id" int DEFAULT nextval('sotf_prog_rating_seq') NOT NULL,
	"node_id" int2 REFERENCES sotf_nodes(id) ON DELETE CASCADE,
	"last_change" timestamptz DEFAULT CURRENT_TIMESTAMP,
-- end of basic stuff
	"prog_id" int NOT NULL,											-- id of programme rated
	"rating_value" float,											-- value of rating
	"rating_count_reg" int,											-- number of registered raters	
	"rating_count_anon" int,										-- number of anonymous raters
	CONSTRAINT "sotf_prog_rating_pkey" PRIMARY KEY ("node_id","id"),
	FOREIGN KEY("prog_id","node_id") REFERENCES sotf_programmes("id", "node_id") ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_refs_seq";

CREATE TABLE "sotf_refs" (
-- referencing portal URLs for a radio programme
-- REPLICATED
	"id" int DEFAULT nextval('sotf_refs_seq') NOT NULL,
	"node_id" int2 REFERENCES sotf_nodes(id) ON DELETE CASCADE,
	"last_change" timestamptz DEFAULT CURRENT_TIMESTAMP,
-- end of basic stuff
	"prog_id" int NOT NULL,							-- programme being referenced
	"station_id" int NOT NULL,
	"url" varchar(255) NOT NULL,					-- URL of portal article referencing to the program
	"comments" int2 DEFAULT '0',					-- number of comments
	CONSTRAINT "sotf_refs_u" UNIQUE ("prog_id", "url"),
	CONSTRAINT "sotf_refs_pkey" PRIMARY KEY ("node_id","id"),
	FOREIGN KEY("prog_id","node_id") REFERENCES sotf_programmes("id", "node_id") ON DELETE CASCADE
);

CREATE SEQUENCE "sotf_stats_seq";

CREATE TABLE "sotf_stats" (
-- download and listen statistics for a radio programme
-- REPLICATED
	"id" int DEFAULT nextval('sotf_stats_seq') NOT NULL,
	"node_id" int2 REFERENCES sotf_nodes(id) ON DELETE CASCADE,
	"last_change" timestamptz DEFAULT CURRENT_TIMESTAMP,
-- end of basic stuff
	"prog_id" int NOT NULL,
	"station_id" int NOT NULL,
	"year" int2 NOT NULL,
	"month" int2 NOT NULL,
	"week" int2 NOT NULL,
	"day" int2 NOT NULL,
	"listens" int DEFAULT '0',					-- number of listens
	"downloads" int DEFAULT '0',				-- number of downloads
	"visits" int DEFAULT '0',					-- number of times page has been visited
	CONSTRAINT "sotf_stats_u" UNIQUE ("prog_id", "month", "year", "day"),
	CONSTRAINT "sotf_stats_pkey" PRIMARY KEY ("node_id","id"),
	FOREIGN KEY("prog_id","node_id") REFERENCES sotf_programmes("id", "node_id") ON DELETE CASCADE
);

