
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

