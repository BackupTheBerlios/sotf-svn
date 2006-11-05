-- -*- tab-width: 2; indent-tabs-mode: 1; -*-

--  $Id$
--
-- Created for the StreamOnTheFly project (IST-2001-32226)
-- Author: András Micsik at MTA SZTAKI DSD, http://dsd.sztaki.hu
--
-- This file collects changes in db structure after 2003-06-04
-- Select your date range, and run the SQL commands in there
-- 

-- 2003-06-05 

-- if you have no station software attached, this may not cause any problem

DROP TABLE "sotf_station_mappings";
DROP SEQUENCE "sotf_station_mappings_id_seq";

CREATE TABLE "sotf_station_mappings" (
-- provides mapping between ids on station server and ids on node XXX
	"id" serial PRIMARY KEY,		-- just an id
	"id_at_node" varchar(12) UNIQUE REFERENCES sotf_node_objects(id) ON DELETE CASCADE,		-- id of thing at node
	"id_at_station" varchar(20) UNIQUE  -- id of thing on station server
);

-- 2003-06-06
-- longer fields for phone numbers

CREATE TABLE "sotf_contacts_1054910234" AS SELECT "id", "name", "alias", "acronym", "intro", "email", "address",  "phone", "cellphone", "fax", "url" FROM "sotf_contacts";
DROP TABLE "sotf_contacts";
CREATE TABLE "sotf_contacts" (
	"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
	"name" varchar(100) NOT NULL,
	"alias" varchar(100),
	"acronym" varchar(30),
	"intro" text,
	"email" varchar(100),
	"address" varchar(255),
	"phone" varchar(50),
	"cellphone" varchar(50),
	"fax" varchar(50),
	"url" varchar(255)
);
INSERT INTO "sotf_contacts" SELECT * FROM "sotf_contacts_1054910234";
DROP TABLE "sotf_contacts_1054910234";

-- 2003-06-11

-- change in contacts handling

ALTER TABLE sotf_contacts ADD COLUMN "station_id" varchar(12) REFERENCES sotf_stations(id) ON DELETE CASCADE;

UPDATE sotf_contacts SET station_id=pr.station_id FROM sotf_object_roles r, sotf_programmes pr WHERE r.contact_id=sotf_contacts.id AND r.object_id=pr.id; 
UPDATE sotf_contacts SET station_id=se.station_id FROM sotf_object_roles r, sotf_series se WHERE r.contact_id=sotf_contacts.id AND r.object_id=se.id; 
UPDATE sotf_contacts SET station_id=r.object_id FROM sotf_object_roles r WHERE r.contact_id=sotf_contacts.id AND r.object_id LIKE '%st%';

-- 2003-06-13

-- change in permission system

DELETE FROM "sotf_permissions" WHERE permission='add_prog';
SELECT * FROM "sotf_user_permissions" where permission_id=3; -- if any exists, you may change these permissions to 4 (create)
-- DELETE FROM "sotf_user_permissions" where permission_id=3; 

-- 2003-06-18

ALTER TABLE sotf_streams ADD COLUMN "url" varchar(200);

-- 2003-06-20

UPDATE sotf_roles SET creator='t' WHERE role_id=2;
UPDATE sotf_roles SET creator='t' WHERE role_id=8;
UPDATE sotf_roles SET creator='t' WHERE role_id=9;
UPDATE sotf_roles SET creator='t' WHERE role_id=22;

ALTER TABLE "sotf_prog_refs" ADD COLUMN "portal_name" varchar(200);
ALTER TABLE "sotf_prog_refs" ADD COLUMN "portal_home" varchar(200);

ALTER TABLE "sotf_comments" ADD COLUMN "comment_title" text;

CREATE SEQUENCE "sotf_portals_seq";

CREATE TABLE "sotf_portals" (
-- list of portals connected to this node 
-- REPLICATED
	"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
	"name" varchar(50) NOT NULL,				  -- name of portal
	"language" varchar(40),						    -- 3-letter codes separeted by comma
	"url" varchar(255) UNIQUE NOT NULL,		-- url of portal (identifies portal)
	"page_impression" int,						    -- number of downloads of starting page 
	"reg_users" int2,								      -- number of registered users
	"last_access" timestamptz,
	"last_update" timestamptz
);

-- 2003-06-26

UPDATE sotf_roles SET creator='t' WHERE role_id=5;
UPDATE sotf_roles SET creator='t' WHERE role_id=12;
UPDATE sotf_roles SET creator='t' WHERE role_id=16;
UPDATE sotf_roles SET creator='t' WHERE role_id=24;

ALTER TABLE sotf_comments ADD COLUMN "sent" bool;
ALTER TABLE sotf_comments ALTER sent SET DEFAULT 'f'::bool;

ALTER TABLE sotf_user_prefs ADD COLUMN "feedback" bool;
ALTER TABLE sotf_user_prefs ALTER feedback SET DEFAULT 't'::bool;

ALTER TABLE sotf_contacts ADD COLUMN "feedback" bool;
ALTER TABLE sotf_contacts ALTER feedback SET DEFAULT 'f'::bool;

-- 2003-07-29

-- it's better to allow stations with the same name
DROP INDEX sotf_stations_name_key;
CREATE INDEX sotf_stations_name_index ON sotf_stations (name);

-- 2003-09-15

-- changed broadcast time from date to timestamp

CREATE TABLE "sotf_programmes_1063641060" AS SELECT "id", "guid", "station_id", "series_id", "track", "foreign_id", "title", "alternative_title", "episode_title", "episode_sequence", "is_part_of", "keywords", "abstract", "entry_date", "production_date", "broadcast_date", "modify_date", "expiry_date", "type", "genre_id", "length", "language", "spatial_coverage", "temporal_coverage", "published" FROM "sotf_programmes";

DROP TABLE "sotf_programmes";

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
FOREIGN KEY("series_id") REFERENCES sotf_series("id") ON DELETE CASCADE
);
CREATE INDEX "prg_lang_idx" ON "sotf_programmes" ("language");

INSERT INTO "sotf_programmes" SELECT * FROM "sotf_programmes_1063641060";
DROP TABLE "sotf_programmes_1063641060";

-- 2003-10-03

-- drop a trigger: can't be automatized, please do the following:
-- select the trigger which is for table sotf_prog_topics and references table sotf_topic_tree_defs and drop it

-- 2003-10-13

-- mapping between ids at stations and nodes was not perfect
DROP INDEX "sotf_station__id_at_station_key";
ALTER TABLE sotf_station_mappings ADD COLUMN "type" varchar(30);
ALTER TABLE sotf_station_mappings ADD COLUMN "station" varchar(12);
CREATE UNIQUE INDEX sotf_station_mappings_uniq ON sotf_station_mappings ("id_at_station", "station", "type");

-- ???? somewhere around Xmas

-- added sotf_users table and ftp_auth view, so sotf can work now without SADM

-- 2004-02-23

-- Some acceleration for topic browsing
CREATE INDEX "supertopic_sotf_topic_tree_defs_key" ON "sotf_topic_tree_defs"("supertopic");
CREATE INDEX "topic_id_sotf_topics_key" ON "sotf_topics"("topic_id");
CREATE UNIQUE INDEX "topic_id_sotf_topics_counter_ukey" ON "sotf_topics_counter"("topic_id");
CREATE INDEX "topic_id_sotf_prog_topics_key" ON "sotf_prog_topics"("topic_id");
CREATE INDEX "prog_id_sotf_prog_topics_key" ON "sotf_prog_topics"("prog_id");

-- 2004-03-02

-- VERY IMPORTANT!
-- you have to rename all station dirs under node/repository!
-- do commands like: mv Test_Radio 011st2
-- also sotf_stations.name field became longer, but not a big problem

-- 2004-10-19

ALTER TABLE "sotf_deletions" ADD "del_time" timestamptz;

-- 2005-05-04

DROP INDEX "sotf_media_files_u";
CREATE INDEX "sotf_media_files_f" ON "sotf_media_files"("filename");

-- 2005-07-04
-- removed default of now + 56 days for expiry_date in sotf_programmes
ALTER TABLE sotf_programmes ALTER COLUMN expiry_date DROP DEFAULT;


-- 2006-02-18
-- ADDED BY Martin Schmidt
-- NEW METADATA COLUMNS FOR VIDEO

BEGIN;
ALTER TABLE sotf_media_files ADD COLUMN "codec" varchar(40) NULL;
ALTER TABLE sotf_media_files ADD COLUMN "frame_rate" int NULL;
ALTER TABLE sotf_media_files ADD COLUMN "lossless" boolean NULL;
ALTER TABLE sotf_media_files ADD COLUMN "resolution_x" float NULL;
ALTER TABLE sotf_media_files ADD COLUMN "resolution_y" float NULL;
ALTER TABLE sotf_media_files ADD COLUMN "pixel_aspect_ratio" float NULL;
COMMIT;

       BEGIN;
       ALTER TABLE sotf_media_files ADD COLUMN frame_rate_2 float;
       UPDATE sotf_media_files SET frame_rate_2 = CAST(frame_rate AS float);
       ALTER TABLE sotf_media_files DROP COLUMN frame_rate;
	   ALTER TABLE sotf_media_files RENAME COLUMN frame_rate_2 TO frame_rate;
       COMMIT;
	   
	   BEGIN;
       ALTER TABLE sotf_media_files ADD COLUMN kbps_2 int;
       UPDATE sotf_media_files SET kbps_2 = CAST(kbps AS int);
       ALTER TABLE sotf_media_files DROP COLUMN kbps;
	   ALTER TABLE sotf_media_files RENAME COLUMN kbps_2 TO kbps;
       COMMIT;
	   
	   BEGIN;
       ALTER TABLE sotf_media_files ADD COLUMN resolution_x_2 float;
       UPDATE sotf_media_files SET resolution_x_2 = CAST(resolution_x AS float);
       ALTER TABLE sotf_media_files DROP COLUMN resolution_x;
	   ALTER TABLE sotf_media_files RENAME COLUMN resolution_x_2 TO resolution_x;
       COMMIT;
	   
	   BEGIN;
       ALTER TABLE sotf_media_files ADD COLUMN resolution_y_2 float;
       UPDATE sotf_media_files SET resolution_y_2 = CAST(resolution_y AS float);
       ALTER TABLE sotf_media_files DROP COLUMN resolution_y;
	   ALTER TABLE sotf_media_files RENAME COLUMN resolution_y_2 TO resolution_y;
       COMMIT;

-- Changes for restricted and payable content
-- 2006-09-30

INSERT INTO "sotf_permissions" ("id", "permission") VALUES('7', 'listen');
SELECT nextval('sotf_permissions_id_seq');

CREATE TABLE "sotf_groups" (
-- user groups for access permissions
"id" serial PRIMARY KEY,	-- just an id
"name" varchar(254) NOT NULL,	-- name of group
"comments" text			-- comments
);
CREATE UNIQUE INDEX sotf_group_name ON sotf_groups ("name");

CREATE TABLE "sotf_user_groups" (
-- stores editor-specific private settings for programmes
"id" serial PRIMARY KEY,		-- just an id
"user_id"  int,				-- id of user
"group_id" int CONSTRAINT "to_groups" REFERENCES sotf_groups(id) ON DELETE CASCADE	-- id of group
);
CREATE UNIQUE INDEX sotf_user_groups_uniq ON sotf_user_groups ("user_id", "group_id");

CREATE TABLE "sotf_group_permissions" (
-- a user group may have a set of permissions on object or globally
"id" serial PRIMARY KEY, -- just an id
"group_id" int CONSTRAINT "to_groups" REFERENCES sotf_groups(id) ON DELETE CASCADE, 
"object_id" varchar(12), 
-- the object in which group permissions apply (can be object_id or string: node, topictree, etc.)
"permission_id" int CONSTRAINT "to_permissions" REFERENCES sotf_permissions(id) ON DELETE CASCADE,
CONSTRAINT "sotf_group_permissions_uniq" UNIQUE ("group_id", "object_id", "permission_id")
);

ALTER TABLE sotf_programmes ADD COLUMN free_content bool;
ALTER TABLE sotf_programmes ALTER free_content SET DEFAULT 'f'::bool;

ALTER TABLE sotf_user_history RENAME COLUMN "when" TO action_date;

CREATE TABLE "sotf_user_data" (
-- customizable registration data
"id" serial PRIMARY KEY, -- just an id
"user_id" int UNIQUE NOT NULL,
"company" varchar(255),
"sector" int,
"guild" int,
"contact_person" varchar(255),
"address" text,
"phone" varchar(30),
"fax" varchar(30),
"mobile" varchar(30),
"skype" varchar(40),
"website" varchar(60),
"nl_health_network" bool DEFAULT 'f'::bool,
"nl_meri_med" bool DEFAULT 'f'::bool,
"nl_update" bool DEFAULT 'f'::bool
);

-- Further changes for restricted and payable content
-- 2006-11-05

ALTER TABLE sotf_programmes ADD COLUMN promoted bool;
ALTER TABLE sotf_programmes ALTER promoted SET DEFAULT 'f'::bool;

ALTER TABLE sotf_groups ADD COLUMN price float;
ALTER TABLE sotf_groups ALTER price SET DEFAULT 0;

