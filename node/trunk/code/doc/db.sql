-- -*- tab-width: 3; indent-tabs-mode: 1; -*-

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
	"userid" varchar(50) PRIMARY KEY,
	"prefs" text
);

CREATE TABLE "sotf_neighbours" (
-- the neighbours of this node
	"id" varchar(40) NOT NULL,
	"last_sync_from" timestamptz,
	"last_sync_to" timestamptz,
	CONSTRAINT "sotf_neighbours_pkey" PRIMARY KEY ("id")
);

CREATE TABLE "sotf_nodes" (
-- data about nodes in the network
	"id" varchar(40) NOT NULL,
	"url" varchar(255) NOT NULL,
	"authorizer" varchar(40) NOT NULL,
	"ip" inet,
	"description" varchar(255),
	"up" bool NOT NULL,
	"last_sync" timestamptz,
	"last_change" timestamptz,
	CONSTRAINT "sotf_repositories_pkey" PRIMARY KEY ("id")
);

CREATE TABLE "sotf_programmes" (
-- used to store generic and searchable metadata about radio programmes
	"id" varchar(76) PRIMARY KEY,									-- globally unique id
	"station" varchar(32) NOT NULL,								-- dc.publisher ??
	"series" varchar(40),											-- this prog is part of series
	"track" varchar(32) NOT NULL,									-- part of id: unique within station and entry_date
	"owner" varchar(255) DEFAULT 'nobody' NOT NULL,			-- who has rights to modify these metadata
	"title" varchar(255) DEFAULT 'untitled',					-- dc.title
	"author" varchar(255),											-- dc.creator
	"keywords" text, 													-- dc.subject
	"abstract" text,													-- dc.description
	"entry_date" date DEFAULT date('now'::text) NOT NULL,	-- dc.date.available
	"production_date" date DEFAULT date('now'::text),		-- dc.date.created
	"broadcast_date" date,											-- dc.date.issued
	"modify_date" date,												-- dc.date.modified
	"expiry_date" date DEFAULT (timestamptz(date('now'::text)) + '56 days'::"interval"),	-- when programme will be made unavailable
	"type" varchar(40) DEFAULT 'sound',							-- DCMI type (audio/video/etc.)
	"genre" varchar(40),												-- SOMA genre
	"length" int2,														-- dc.format.extent = duration in seconds
	"language" char(3),												-- dc.language
	"spatial_coverage" text,										-- dc.coverage.spatial
	"temporal_coverage" date,										-- dc.coverage.temporal
	"icon" bytea,														-- small image associated with prog
	"contact_email" varchar(80),									-- e-mail of contact person
	"contact_phone" varchar(20),									-- phone of contact person
	"rating_value" float,											-- value of rating
	"rating_count_reg" int,											-- number of registered raters	
	"rating_count_anon" int,										-- number of anonymous raters
	"published" bool DEFAULT 'f'::bool,							-- unpublished items are not searchable nor browsable
	"last_change" timestamptz DEFAULT ('now'::text)::timestamp(6) with time zone		-- needed for synchronization of nodes
);

CREATE TABLE "sotf_extradata" (
-- generic metadata storage (better would be an RDF store)
-- the use of this table is not clear yet
	"id" varchar(150) NOT NULL,
	"element" varchar(40) NOT NULL,
	"qualifier" varchar(40),
	"scheme" varchar(50),
	"language" char(2) NOT NULL,
	"value" text,
	CONSTRAINT "sotf_extradata_uniq" UNIQUE ("id", "element", "qualifier", "scheme", "value")
);

CREATE TABLE "sotf_files" (
-- permissions on associated audio and other files for a radio programme
	"id" varchar(76) NOT NULL,
	"filename" varchar(32) NOT NULL,
	"audio" bool DEFAULT 'f'::bool,
	"listen" bool DEFAULT 'f'::bool,
	"download" bool DEFAULT 'f'::bool,
	"last_change" timestamptz,
	CONSTRAINT "sotf_files_pkey" PRIMARY KEY ("id", "filename")
);

CREATE TABLE "sotf_links" (
-- web links associated with a radio programme
	"id" varchar(76) NOT NULL,
	"url" varchar(255) NOT NULL,
	CONSTRAINT "sotf_links_pkey" PRIMARY KEY ("id", "url")
);

CREATE TABLE "sotf_prg_topics" (
-- defines the topics associated with a radio programme
	"id" varchar(76) NOT NULL,
	"tree_id" int NOT NULL,
	"topic_id" int NOT NULL,
	CONSTRAINT "sotf_prg_topics_pkey" PRIMARY KEY ("topic_id", "tree_id", "id")
);

CREATE TABLE "sotf_stations" (
	"station" varchar(32) PRIMARY KEY,
	"node" varchar(20),
	"description" varchar(250),
	"contact_email" varchar(60),
	"contact_phone" varchar(20),
	"icon" bytea,
	"jingle" bytea,
	"last_change" timestamptz
);

CREATE TABLE "sotf_series" (
	"id" varchar(65) PRIMARY KEY,
	"station" varchar(32) NOT NULL,
	"series_id" varchar(32) NOT NULL,
	"editor" varchar(255),
	"title" varchar(255) DEFAULT 'untitled' NOT NULL,
	"description" text,
	"contact_email" varchar(60),
	"contact_phone" varchar(20),
	"icon" bytea,
	"jingle" bytea,
	"last_change" timestamptz
);

CREATE TABLE "sotf_contact" (
-- this is a person or organization record
	"id" varchar(100) PRIMARY KEY,
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
	"logo" bytea
)

CREATE TABLE "sotf_deletions" (
-- remember and propagate deletions to other nodes
	"what" varchar(10) NOT NULL,
	"id" varchar(100) NOT NULL,
	"del_time" timestamptz NOT NULL,
	"node" varchar(20),
	CONSTRAINT "sotf_del_pkey" PRIMARY KEY ("id")
);


CREATE TABLE "sotf_refs" (
-- referencing protal URLs for a radio programme
	"id" varchar(76) NOT NULL,
	"station" varchar(32) NOT NULL,
	"url" varchar(255) NOT NULL,
	"comments" int2 DEFAULT '0',
	CONSTRAINT "sotf_refs_pkey" PRIMARY KEY ("id", "url")
);

CREATE TABLE "sotf_stats" (
-- download and listen statistics for a radio programme
	"id" varchar(76) NOT NULL,
	"station" varchar(32) NOT NULL,
	"year" int2 NOT NULL,
	"month" int2 NOT NULL,
	"week" int2 NOT NULL,
	"day" int2 NOT NULL,
	"listens" int2,
	"downloads" int2,
	CONSTRAINT "sotf_stats_pkey" PRIMARY KEY ("id", "month", "year", "day")
);

CREATE TABLE sotf_ratings (
-- individual ratings made by registered persons or anonym users
	"id" varchar(76) NOT NULL,
	"username" varchar(50) default NULL,
	"rate" SMALLINT NOT NULL default '0',
	"host" varchar(100) NOT NULL default '',
	"entered" datetime NOT NULL default '-infinity',
	"auth_key" varchar(50),
	"problem" varchar(50) default NULL,
	CONSTRAINT "sotf_ratings_uniq" UNIQUE ("id", "entered", "auth_key") -- this is not perfect
);

CREATE TABLE sotf_bookmarks (
-- registered users may bookmark things
	"id" varchar(76) NOT NULL,
	"username" varchar(50) NOT NULL,
	"type" VARCHAR(10), -- bookmark or playlist? - use unclear yet
	CONSTRAINT "sotf_bookmarks_pkey" PRIMARY KEY ("id", "username")
)
