/* -------------------------------------------------------- 
  phpPgAdmin 2.4-1 DB Dump
  http://sourceforge.net/projects/phppgadmin/
  Host: localhost:5432
  Database  : "sotf_station"
  2003-05-12 13:05:21
-------------------------------------------------------- */ 

/* -------------------------------------------------------- 
  Sequences 
-------------------------------------------------------- */ 
CREATE SEQUENCE "help_texts_id_seq" START 1 INCREMENT 1; 
SELECT NEXTVAL('help_texts_id_seq'); 
CREATE SEQUENCE "programme_id_seq" START 1 INCREMENT 1; 
SELECT NEXTVAL('programme_id_seq'); 
CREATE SEQUENCE "series_id_seq" START 1 INCREMENT 1; 
SELECT NEXTVAL('series_id_seq'); 
CREATE SEQUENCE "user_access_id_seq" START 1 INCREMENT 1; 
SELECT NEXTVAL('user_access_id_seq'); 
CREATE SEQUENCE "user_log_id_seq" START 1 INCREMENT 1; 
SELECT NEXTVAL('user_log_id_seq'); 

/* -------------------------------------------------------- 
  Table structure for table "help_texts" 
-------------------------------------------------------- */
CREATE TABLE "help_texts" (
   "id" int4 DEFAULT nextval('"help_texts_id_seq"'::text) NOT NULL,
   "title" varchar(255) NOT NULL,
   "content" text NOT NULL,
   CONSTRAINT "help_texts_pkey" PRIMARY KEY ("id")
);


/* -------------------------------------------------------- 
  Dumping data for table "help_texts" 
-------------------------------------------------------- */ 
INSERT INTO "help_texts" ("id", "title", "content") VALUES(1, 'Logging In', 'To login into the station management console you will have to enter a user name and a password, which must have been allocated to you by the station administrator.');
INSERT INTO "help_texts" ("id", "title", "content") VALUES(2, 'Personal Settings', 'This panel allows you to fully configure your personal user settings for the Station Management Console. <br><br>

<b>Show Records Per Page</b><Br>
Use this drop down box to choose the number of records that will be shown per page when managing your series, browsing through the user list or managing access logs.
<br><Br>
<b>Autologin</b><br>
If you do not wish to enter your user name and password every time that you wish to use the station management software, then check this box. Please note, that you have to be able to accept cookies in order to use this feature.
<br><br>
<b>Real Name</b><br>
This is the name that will be shown on the program overview pane, please use something sensible here.
<br><br>
<b>User’s E-mail</b><br>
This is your primary e-mail, all the notifications and messages from the station administrator will be forwarded to this address, Make sure you keep it up to date.');
INSERT INTO "help_texts" ("id", "title", "content") VALUES(3, 'User Panel', 'Using this panel you can see and manage all the people, who currently run your station. The above panel shows you an overview of all the data, listing the user’s ID, real name, status and his/her involvement into the operation of the station.<br><br>

You can sort all the data, choose a parameter to sort the data by, by clicking on the appropriate column name. The default sort order is: ascending. If you wish to sort everything in descending order, then simply click on this column again.<br><br>

To edit the data of any selected user, click on the EDIT icon in the Options column and manage the data in the popup window. If you wish to delete some user, then click on the DELETE icon and confirm your action by selecting OK in the dialog box.<br><br>');

/* -------------------------------------------------------- 
  Table structure for table "programme" 
-------------------------------------------------------- */
CREATE TABLE "programme" (
   "id" int4 DEFAULT nextval('"programme_id_seq"'::text) NOT NULL,
   "series_id" int4 NOT NULL,
   "intime" timestamptz,
   "outtime" timestamptz,
   "title" varchar(255),
   "special" varchar(2),
   "active" bool DEFAULT 't'::bool,
   "alt_title" varchar(255),
   "keywords" text,
   "description" text,
   "contributors" text,
   "created" date DEFAULT now(),
   "issued" date DEFAULT now(),
   "topic" varchar(100),
   "genre" varchar(100),
   "lang" varchar(3) DEFAULT 'eng',
   "rights" text,
   "published" timestamptz,
   CONSTRAINT "programme_pkey" PRIMARY KEY ("id")
);
CREATE  INDEX "active_programme_key" ON "programme" ("active");
CREATE  INDEX "intime_programme_key" ON "programme" ("intime");
CREATE  INDEX "outtime_programme_key" ON "programme" ("outtime");
CREATE  INDEX "programme_series_id_key" ON "programme" ("series_id");


/* -------------------------------------------------------- 
  Dumping data for table "programme" 
-------------------------------------------------------- */ 

/* -------------------------------------------------------- 
  Table structure for table "series" 
-------------------------------------------------------- */
CREATE TABLE "series" (
   "id" int4 DEFAULT nextval('"series_id_seq"'::text) NOT NULL,
   "owner" int4 NOT NULL,
   "title" varchar(255) NOT NULL,
   "description" text NOT NULL,
   "intime" date DEFAULT date('now'::text) NOT NULL,
   "active" bool DEFAULT 't'::bool,
   CONSTRAINT "series_pkey" PRIMARY KEY ("id")
);
CREATE  INDEX "active_series_key" ON "series" ("active");
CREATE  INDEX "series_intime_key" ON "series" ("intime");
CREATE  INDEX "series_owner_key" ON "series" ("owner");


/* -------------------------------------------------------- 
  Dumping data for table "series" 
-------------------------------------------------------- */ 

/* -------------------------------------------------------- 
  Table structure for table "user_access" 
-------------------------------------------------------- */
CREATE TABLE "user_access" (
   "id" int4 DEFAULT nextval('"user_access_id_seq"'::text) NOT NULL,
   "name" varchar(255) NOT NULL,
   "edit_series" int2 NOT NULL,
   "edit_station" int2 NOT NULL,
   "edit_users" int2 NOT NULL,
   "edit_presentbox" int2 DEFAULT 1,
   CONSTRAINT "user_access_pkey" PRIMARY KEY ("id")
);
CREATE  INDEX "user_access_edit_series_key" ON "user_access" ("edit_series");
CREATE  INDEX "user_access_edit_station_key" ON "user_access" ("edit_station");
CREATE  INDEX "user_access_edit_users_key" ON "user_access" ("edit_users");
CREATE  UNIQUE INDEX "user_access_name_key" ON "user_access" ("name");


/* -------------------------------------------------------- 
  Dumping data for table "user_access" 
-------------------------------------------------------- */ 
INSERT INTO "user_access" ("id", "name", "edit_series", "edit_station", "edit_users", "edit_presentbox") VALUES(3, 'Series Manager', 2, 1, 1, 1);
INSERT INTO "user_access" ("id", "name", "edit_series", "edit_station", "edit_users", "edit_presentbox") VALUES(4, 'Spectator', 1, 1, 1, 1);
INSERT INTO "user_access" ("id", "name", "edit_series", "edit_station", "edit_users", "edit_presentbox") VALUES(1, 'Administrator', 2, 2, 2, 2);
INSERT INTO "user_access" ("id", "name", "edit_series", "edit_station", "edit_users", "edit_presentbox") VALUES(2, 'Station Manager', 2, 2, 1, 2);

/* -------------------------------------------------------- 
  Table structure for table "user_autologin" 
-------------------------------------------------------- */
CREATE TABLE "user_autologin" (
   "auth_id" int4 NOT NULL,
   "next_key" varchar(255),
   CONSTRAINT "user_autologin_pkey" PRIMARY KEY ("auth_id")
);


/* -------------------------------------------------------- 
  Dumping data for table "user_autologin" 
-------------------------------------------------------- */ 

/* -------------------------------------------------------- 
  Table structure for table "user_log" 
-------------------------------------------------------- */
CREATE TABLE "user_log" (
   "id" int4 DEFAULT nextval('"user_log_id_seq"'::text) NOT NULL,
   "auth_id" int4 NOT NULL,
   "intime" timestamptz DEFAULT now() NOT NULL,
   "action" int4,
   CONSTRAINT "user_log_pkey" PRIMARY KEY ("id")
);
CREATE  INDEX "action_user_log_key" ON "user_log" ("action");
CREATE  INDEX "user_log_auth_id_key" ON "user_log" ("auth_id");
CREATE  INDEX "user_log_intime_key" ON "user_log" ("intime");


/* -------------------------------------------------------- 
  Dumping data for table "user_log" 
-------------------------------------------------------- */ 

/* -------------------------------------------------------- 
  Table structure for table "user_map" 
-------------------------------------------------------- */
CREATE TABLE "user_map" (
   "auth_id" int4 NOT NULL,
   "access_id" int2 NOT NULL,
   "name" varchar(255),
   "mail" varchar(255),
   "per_page" int2 DEFAULT 20,
   "role" varchar(255)
);
CREATE  UNIQUE INDEX "auth_id_user_map_ukey" ON "user_map" ("auth_id");


/* -------------------------------------------------------- 
  Dumping data for table "user_map" 
-------------------------------------------------------- */ 
INSERT INTO "user_map" ("auth_id", "access_id", "name", "mail", "per_page", "role") VALUES(1, 1, 'Admin', 'admin@admin.com', 20, 'Producer');

/* No Views found */

/* No Functions found */

/* No Triggers found */

