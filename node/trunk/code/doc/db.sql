-- -*- tab-width: 3; indent-tabs-mode: 1; -*-
-- todo: define foreign keys, references, checks

CREATE TABLE "sotf_vars" (
-- global persistent server variables
	"name" varchar(32) PRIMARY KEY,
	"value" varchar(255) NOT NULL
);

CREATE TABLE "sotf_group_permission" (
-- a group can have a set of permissions
	"group_id" varchar(50) DEFAULT '' NOT NULL,
	"permission" varchar(20) DEFAULT '' NOT NULL,
	CONSTRAINT "sotf_group_permission_pkey" PRIMARY KEY ("group_id", "permission")
);

INSERT INTO "sotf_group_permission" ("group_id", "permission") VALUES('guest', 'read');
INSERT INTO "sotf_group_permission" ("group_id", "permission") VALUES('station_manager', 'station_manager');
INSERT INTO "sotf_group_permission" ("group_id", "permission") VALUES('editor', 'read');
INSERT INTO "sotf_group_permission" ("group_id", "permission") VALUES('editor', 'write');
INSERT INTO "sotf_group_permission" ("group_id", "permission") VALUES('editor', 'upload');
INSERT INTO "sotf_group_permission" ("group_id", "permission") VALUES('admin', 'read');
INSERT INTO "sotf_group_permission" ("group_id", "permission") VALUES('admin', 'write');
INSERT INTO "sotf_group_permission" ("group_id", "permission") VALUES('admin', 'upload');
INSERT INTO "sotf_group_permission" ("group_id", "permission") VALUES('admin', 'station_manager');

CREATE TABLE "sotf_user_group" (
-- a user may belong to global or station-local groups thus defining permissions 
-- for him as in table sotf_group_permission
	"username" varchar(50) DEFAULT 'nobody' NOT NULL,
	"station" varchar(50),
	"group_id" varchar(50) DEFAULT '' NOT NULL,
	CONSTRAINT "sotf_user_group_uniq" UNIQUE ("username", "station", "group_id")
);

INSERT INTO "sotf_user_group" ("username", "station", "group_id") VALUES('admin', '', 'station_manager');

CREATE TABLE "sotf_user_prefs" (
-- user preferences stored as serialized objects
	"username" varchar(50) PRIMARY KEY,
	"prefs" text
);

CREATE TABLE "sotf_playlists" (
-- registered users may bookmark things
	"prog_id" varchar(76) NOT NULL,
	"username" varchar(50) NOT NULL,
	"type" VARCHAR(10), -- use unclear yet
	CONSTRAINT "sotf_bookmarks_pkey" PRIMARY KEY ("id", "username")
);

CREATE TABLE "sotf_user_history" (
-- past actions of the user, may be used for collaborative filtering
	"username" varchar(50) NOT NULL,
	"action" VARCHAR(30), -- type of action the user did with 'id'
	"prog_id" varchar(200),
	"when" datetime,
	CONSTRAINT "sotf_history_pkey" PRIMARY KEY ("id", "username")
);

CREATE TABLE "sotf_ratings" (
-- individual ratings made by registered persons or anonym users
	"prog_id" varchar(76) NOT NULL,										-- sotf id
	"username" varchar(50) default NULL,						-- user who rated or NULL if anonymous
	"rate" SMALLINT NOT NULL default '0',
	"host" varchar(100) NOT NULL default '',					-- host from where the rating arrived
	"entered" datetime NOT NULL default '-infinity',		-- date when rating arrived
	"auth_key" varchar(50),											-- anti-abuse thingie
	"problem" varchar(50) default NULL,							-- if any suspicious thing occurred during rating
	CONSTRAINT "sotf_ratings_uniq" UNIQUE ("id", "entered", "auth_key") -- this is not perfect
);

CREATE TABLE "sotf_neighbours" (
-- the neighbours of this node
	"node_id" varchar(40) NOT NULL,
	"accept_incoming" bool DEFAULT 't'::bool,
	"use_for_outgoing" bool DEFAULT 't'::bool,
	"last_incoming" timestamptz,
	"last_outgoing" timestamptz,
	CONSTRAINT "sotf_neighbours_pkey" PRIMARY KEY ("id")
);

CREATE TABLE "sotf_nodes" (
-- data about nodes in the network
	"id" varchar(40) PRIMARY KEY,
	"url" varchar(255) NOT NULL,
	"authorizer" varchar(40) NOT NULL,
	"ip" inet,
	"description" varchar(255),
	"up" bool NOT NULL,
	"last_sync" timestamptz,
	"last_change" timestamptz
);

CREATE TABLE "sotf_programmes" (
-- used to store generic and searchable metadata about radio programmes
	"id" varchar(76) PRIMARY KEY,									-- globally unique id
	"station" varchar(32) NOT NULL,								-- dc.publisher ??
	"series" varchar(40),											-- this prog is part of series
	"track" varchar(32) NOT NULL,									-- part of id: unique within station and entry_date
	"owner" varchar(255) DEFAULT 'nobody' NOT NULL,			-- who has rights to modify these metadata
	"title" varchar(255) DEFAULT 'untitled',					-- dc.title
	"alternative_title" varchar(255), 							-- may be known under a different title
	"episode_title" varchar(255),									-- may be used if the show is part of a series
	"episode_sequence" int4,										-- may be used if the show is in a series
	"keywords" text, 													-- dc.subject (free keywords)
--VAGY:        "subject" varchar(76) foreign key references sotf_subjects.id
-- subject.id
        --(Text and encoding scheme to be found in the sotf_subjects table)
	"abstract" text,													-- dc.description
	"entry_date" date DEFAULT date('now'::text) NOT NULL,	-- dc.date.available
	"production_date" date DEFAULT date('now'::text),		-- dc.date.created
	"broadcast_date" date,											-- dc.date.issued
	"modify_date" date,												-- dc.date.modified
	"expiry_date" date DEFAULT (timestamptz(date('now'::text)) + '56 days'::"interval"),	-- when programme will be made unavailable
	"type" varchar(50) DEFAULT 'sound',							-- DCMI type (audio/video/etc.)
	"genre" varchar(50),												-- SOMA genre
	"length" int2,														-- dc.format.extent = duration in seconds
	"language" varchar(10),												-- dc.language
	"spatial_coverage" text,										-- dc.coverage.spatial
	"temporal_coverage" date,										-- dc.coverage.temporal
	"icon" bytea,														-- small image associated with prog
	"rating_value" float,											-- value of rating
	"rating_count_reg" int,											-- number of registered raters	
	"rating_count_anon" int,										-- number of anonymous raters
	"published" bool DEFAULT 'f'::bool,							-- unpublished items are not searchable nor browsable
	"last_change" timestamptz DEFAULT ('now'::text)::timestamp(6) with time zone		-- needed for synchronization of nodes
);

CREATE TABLE "sotf_rights" (
-- used to store the rights for a programme
	"prog_id" varchar(76) FOREIGN KEY REFERENCES sotf_programmes.id ON delete CASCADE,
	"start_time" int4 NOT NULL,   --startting second of the rightcpntrolled part
	"stop_time"  int4 NOT NULL,  -- ending second. Both may be empty. If so, the rights text is valid for the complete programme
	"rights_text" text
);

CREATE TABLE "sotf_prog_roles" (
-- points to contact records for editors/artists/etc.
	"prog_id" varchar(76) NOT NULL,
	"contact_id" varchar(100) NOT NULL,
	"role" varchar(50) NOT NULL,
	CONSTRAINT "sotf_prog_roles_uniq" UNIQUE ("prog_id", "contact_id", "role")
);

CREATE TABLE "sotf_extradata" (
-- generic metadata storage (better would be an RDF store)
-- the use of this table is not clear yet
	"prog_id" varchar(150) NOT NULL,
	"element" varchar(40) NOT NULL,
	"qualifier" varchar(40),
	"scheme" varchar(50),
	"language" varchar(10) NOT NULL,
	"value" text,
	CONSTRAINT "sotf_extradata_uniq" UNIQUE ("id", "element", "qualifier", "scheme", "value")
);

CREATE TABLE "sotf_files" (
-- permissions on associated audio and other files for a radio programme
	"prog_id" varchar(76) NOT NULL,
	"filename" varchar(32) NOT NULL,
	"caption" varchar(255),
	"filesize" int,
	"audio_length" int,
	"audio" bool DEFAULT 'f'::bool,
	"listen" bool DEFAULT 'f'::bool,
	"download" bool DEFAULT 'f'::bool,
	"last_change" timestamptz,
	CONSTRAINT "sotf_files_pkey" PRIMARY KEY ("id", "filename")
);

CREATE TABLE "sotf_links" (
-- web links associated with a radio programme
	"prog_id" varchar(76) NOT NULL,
	"url" varchar(255) NOT NULL,
	"caption" varchar(255),
	CONSTRAINT "sotf_links_pkey" PRIMARY KEY ("id", "url")
);

CREATE TABLE "sotf_prog_topics" (
-- defines the topics associated with a radio programme
	"prog_id" varchar(76) NOT NULL,
	"tree_id" int NOT NULL,
	"topic_id" int NOT NULL,
	CONSTRAINT "sotf_prog_topics_pkey" PRIMARY KEY ("topic_id", "tree_id", "id")
);

CREATE TABLE "sotf_stations" (
	"id" varchar(32) PRIMARY KEY,
	"node" varchar(20),
	"description" varchar(250),
	"icon" bytea,
	"jingle" bytea,
	"last_change" timestamptz
);

CREATE TABLE "sotf_station_roles" (
-- points to contact records for editors/artists/etc.
	"station_id" varchar(76) NOT NULL,
	"contact_id" varchar(100) NOT NULL,
	"role" varchar(50) NOT NULL,
	CONSTRAINT "sotf_station_roles_uniq" UNIQUE ("station_id", "contact_id", "role")
);

CREATE TABLE "sotf_series" (
-- id = station:series_id
	"id" varchar(65) PRIMARY KEY,
	"station" varchar(32) NOT NULL,
	"series_id" varchar(32) NOT NULL,
	"title" varchar(255) DEFAULT 'untitled' NOT NULL,
	"description" text,
	"owner" varchar(255) DEFAULT 'nobody' NOT NULL,			-- who has rights to modify these metadata
	"editor" varchar(255),  -- use not clear yet
	"icon" bytea,
	"jingle" bytea,
	"last_change" timestamptz
);

CREATE TABLE "sotf_series_roles" (
-- points to contact records for editors/artists/etc.
	"series_id" varchar(76) NOT NULL,
	"contact_id" varchar(100) NOT NULL,
	"role" varchar(50) NOT NULL,
	CONSTRAINT "sotf_series_roles_uniq" UNIQUE ("series_id", "contact_id", "role")
);

CREATE TABLE "sotf_contacts" (
-- this is a person or organization record
	"id" varchar(100) PRIMARY KEY,
	"owner" varchar(255),					-- who has rights to modify these metadata
	"name" varchar(100) NOT NULL,
	"alias" varchar(100),
	"acronym" varchar(20),
	"intro" text,
	"email" varchar(100),
	"address" varchar(255),
	"phone" varchar(20),
	"cellphone" varchar(20),
	"fax" varchar(20),
	"url" varchar(255),
	"icon" bytea,
	"jingle" bytea,
	"last_change" timestamptz
);

CREATE TABLE "sotf_deletions" (
-- remember and propagate deletions to other nodes
-- deletions of many table rows are done via foreign keys!!
	"table_name" varchar(10) NOT NULL,
	"del_id" varchar(100) NOT NULL,
	"del_time" timestamptz NOT NULL,
	"node" varchar(20),
	CONSTRAINT "sotf_del_pkey" PRIMARY KEY ("table_name","del_id")
);

CREATE TABLE "sotf_refs" (
-- referencing portal URLs for a radio programme
	"prog_id" varchar(76) NOT NULL,
	"station" varchar(32) NOT NULL,
	"url" varchar(255) NOT NULL,
	"comments" int2 DEFAULT '0',
	CONSTRAINT "sotf_refs_pkey" PRIMARY KEY ("id", "url")
);

CREATE TABLE "sotf_stats" (
-- download and listen statistics for a radio programme
	"prog_id" varchar(76) NOT NULL,
	"station" varchar(32) NOT NULL,
	"year" int2 NOT NULL,
	"month" int2 NOT NULL,
	"week" int2 NOT NULL,
	"day" int2 NOT NULL,
	"listens" int2,
	"downloads" int2,
	CONSTRAINT "sotf_stats_pkey" PRIMARY KEY ("id", "month", "year", "day")
);


