
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

CREATE TABLE "sotf_portals" (
-- list of portals connected to this node 
-- REPLICATED
	"id" varchar(12) PRIMARY KEY REFERENCES sotf_node_objects(id) ON DELETE CASCADE,
	"name" varchar(50) NOT NULL,				-- name of portal
	"url" varchar(255) UNIQUE NOT NULL,		-- url of portal (identifies portal)
	"page_impression" int,						-- number of downloads of starting page 
	"reg_users" int2,								-- number of registered users
	"last_access" timestamptz,
	"last_update" timestamptz
);

