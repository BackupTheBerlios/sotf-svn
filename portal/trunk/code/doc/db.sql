
--  $Id$
--
-- Database definition for portal engine
--
-- Created for the StreamOnTheFly project (IST-2001-32226)
-- Authors: András Micsik, Máté Pataki, Tamás Déri 
--          at MTA SZTAKI DSD, http://dsd.sztaki.hu


CREATE TABLE "portal_templates" (
   "id" SERIAL PRIMARY KEY,
   "name" varchar NOT NULL,
   "settings" varchar,
   "picture" varchar,
   "published" bool NOT NULL
);

CREATE TABLE "portal_settings" (
"id" SERIAL PRIMARY KEY, 
"name" varchar NOT NULL, 
"template_id" int4 REFERENCES "portal_templates"("id"), 
"admin_id" int4, 
"settings" varchar , 
"password" varchar NOT NULL );

CREATE TABLE "portal_users" (
   "id" SERIAL PRIMARY KEY,
   "portal_id" int4 REFERENCES "portal_settings"("id") NOT NULL,
   "name" varchar NOT NULL,
   "password" varchar NOT NULL,
   "email" varchar,
   "activate" int4
);

CREATE TABLE "portal_prglist" (
"id" SERIAL PRIMARY KEY, 
"portal_id" int4 REFERENCES "portal_settings"("id") NOT NULL, 
"name" varchar);

CREATE TABLE "portal_programmes" (
"id" SERIAL PRIMARY KEY,
"portal_id" int4 REFERENCES "portal_settings"("id") NOT NULL,
"progid" varchar (20) NOT NULL,
"prglist_id" int4 REFERENCES "portal_prglist"("id"));

CREATE TABLE "programmes_description" (
"id" SERIAL PRIMARY KEY,
"portal_id" int4 REFERENCES "portal_settings"("id") NOT NULL,
"progid" varchar (20) NOT NULL,
"teaser" varchar,
"text" varchar);

CREATE TABLE "portal_queries" (
"id" SERIAL PRIMARY KEY, 
"portal_id" int4 REFERENCES "portal_settings"("id") NOT NULL, 
"name" varchar NOT NULL,
"query" varchar);

CREATE TABLE "portal_files" (
"id" SERIAL PRIMARY KEY,
"portal_id" int4 REFERENCES "portal_settings"("id") NOT NULL,
"progid" varchar (20),
"file_location" varchar NOT NULL,
"filename" varchar);

CREATE TABLE programmes_comments (
"id" SERIAL PRIMARY KEY,
"portal_id" int4 REFERENCES portal_settings(id) NOT NULL,
"progid" varchar (20),
"user_id" int4 REFERENCES portal_users(id) NOT NULL,
"reply_to" int4 REFERENCES programmes_comments(id),
"path" varchar,
"timestamp" datetime DEFAULT date('now'::datetime) NOT NULL,
"title" varchar,
"comment" varchar,
"level" int2);

CREATE TABLE "portal_vars" (
   "id" int4 DEFAULT nextval('"portal_vars_id_seq"'::text) NOT NULL,
   "name" varchar(32),
   "value" varchar(255) NOT NULL,
   CONSTRAINT "portal_vars_pkey" PRIMARY KEY ("id")
);
CREATE  UNIQUE INDEX "portal_vars_name_key" ON "portal_vars" ("name");

INSERT INTO "portal_vars" ("id", "name", "value") VALUES(1, 'smarty_compile_check', '1');

INSERT INTO "portal_templates" ("id", "name", "settings", "published") VALUES(1, 'Under construction', 'YTo3OntzOjU6InRhYmxlIjthOjQ6e2k6MDthOjM6e2k6MDthOjEwOntzOjg6InJlc291cmNlIjtzOjQ6InRleHQiO3M6NToidmFsdWUiO3M6MDoiIjtzOjQ6ImxpbmsiO3M6NDoibm9uZSI7czo1OiJzdHlsZSI7czo0OiJub25lIjtzOjU6ImNsYXNzIjtzOjQ6Im5vbmUiO3M6NToiYWxpZ24iO3M6NjoiY2VudGVyIjtzOjY6InZhbGlnbiI7czo2OiJtaWRkbGUiO3M6NToid2lkdGgiO3M6MDoiIjtzOjU6ImNvbG9yIjtzOjY6ImZmMDAwMCI7czo0OiJodG1sIjtzOjA6IiI7fWk6MTthOjEwOntzOjg6InJlc291cmNlIjtzOjQ6InRleHQiO3M6NToidmFsdWUiO3M6MDoiIjtzOjQ6ImxpbmsiO3M6NDoibm9uZSI7czo1OiJzdHlsZSI7czo0OiJub25lIjtzOjU6ImNsYXNzIjtzOjQ6Im5vbmUiO3M6NToiYWxpZ24iO3M6NjoiY2VudGVyIjtzOjY6InZhbGlnbiI7czo2OiJtaWRkbGUiO3M6NToid2lkdGgiO3M6MDoiIjtzOjU6ImNvbG9yIjtzOjY6ImZmZmZmZiI7czo0OiJodG1sIjtzOjA6IiI7fWk6MjthOjEwOntzOjg6InJlc291cmNlIjtzOjQ6InRleHQiO3M6NToidmFsdWUiO3M6MDoiIjtzOjQ6ImxpbmsiO3M6NDoibm9uZSI7czo1OiJzdHlsZSI7czo0OiJub25lIjtzOjU6ImNsYXNzIjtzOjQ6Im5vbmUiO3M6NToiYWxpZ24iO3M6NjoiY2VudGVyIjtzOjY6InZhbGlnbiI7czo2OiJtaWRkbGUiO3M6NToid2lkdGgiO3M6MDoiIjtzOjU6ImNvbG9yIjtzOjY6IjAwODAwMCI7czo0OiJodG1sIjtzOjA6IiI7fX1pOjE7YToxOntpOjA7YToxMDp7czo4OiJyZXNvdXJjZSI7czo0OiJ0ZXh0IjtzOjU6InZhbHVlIjtzOjA6IiI7czo0OiJsaW5rIjtzOjQ6Im5vbmUiO3M6NToic3R5bGUiO3M6NDoibm9uZSI7czo1OiJjbGFzcyI7czo0OiJub25lIjtzOjU6ImFsaWduIjtzOjY6ImNlbnRlciI7czo2OiJ2YWxpZ24iO3M6NjoibWlkZGxlIjtzOjU6IndpZHRoIjtzOjA6IiI7czo1OiJjb2xvciI7czowOiIiO3M6NDoiaHRtbCI7czowOiIiO319aToyO2E6MTp7aTowO2E6MTA6e3M6ODoicmVzb3VyY2UiO3M6NDoidGV4dCI7czo1OiJ2YWx1ZSI7czozNToiVW5kZXIgY29uc3RydWN0aW9uITxicj48aHIgbm9zaGFkZT4iO3M6NDoibGluayI7czo0OiJub25lIjtzOjU6InN0eWxlIjtzOjQwOiJzaXplOjV8ZmFjZTpBcmlhbCwgSGVsdmV0aWNhLCBzYW5zLXNlcmlmIjtzOjU6ImNsYXNzIjtzOjQ6Im5vbmUiO3M6NToiYWxpZ24iO3M6NjoiY2VudGVyIjtzOjY6InZhbGlnbiI7czo2OiJtaWRkbGUiO3M6NToid2lkdGgiO3M6NDoiMTAwJSI7czo1OiJjb2xvciI7czowOiIiO3M6NDoiaHRtbCI7czo5MzoiPGZvbnQgZmFjZT0iQXJpYWwsIEhlbHZldGljYSwgc2Fucy1zZXJpZiIgc2l6ZT0iNSI+VW5kZXIgY29uc3RydWN0aW9uITxicj48aHIgbm9zaGFkZT48L2ZvbnQ+Ijt9fWk6MzthOjE6e2k6MDthOjEwOntzOjg6InJlc291cmNlIjtzOjU6InNwYWNlIjtzOjU6InZhbHVlIjtzOjA6IiI7czo0OiJsaW5rIjtzOjQ6Im5vbmUiO3M6NToic3R5bGUiO3M6NDoibm9uZSI7czo1OiJjbGFzcyI7czo0OiJub25lIjtzOjU6ImFsaWduIjtzOjY6ImNlbnRlciI7czo2OiJ2YWxpZ24iO3M6NjoibWlkZGxlIjtzOjU6IndpZHRoIjtzOjA6IiI7czo1OiJjb2xvciI7czowOiIiO3M6NDoiaHRtbCI7czowOiIiO319fXM6NjoicG9ydGFsIjthOjQ6e3M6MzoiYmcxIjtzOjY6Ijk5ZmY5OSI7czozOiJiZzIiO3M6NjoiNjZjYzAwIjtzOjQ6ImZvbnQiO3M6NjoiMDAwMDAwIjtzOjM6ImNzcyI7Tjt9czo0OiJob21lIjthOjc6e3M6MjoiYmciO3M6NjoiOTlmZjk5IjtzOjQ6IndhbGwiO3M6NDM6Imh0dHA6Ly93d3cuZHNkLnN6dGFraS5odS9+bWF0ZS9wbTIwNS9iZy5qcGciO3M6NDoiZm9udCI7czo2OiIwMDAwMDAiO3M6NDoibGluayI7czo2OiIwMDMzMDAiO3M6NToiYWxpbmsiO3M6NjoiMDA2NjAwIjtzOjU6InZsaW5rIjtzOjY6IjAwNjYwMCI7czozOiJjc3MiO047fXM6MTA6InByb2dyYW1tZXMiO2E6Nzp7czoyOiJiZyI7czo2OiI5OWZmOTkiO3M6NDoid2FsbCI7czo0MzoiaHR0cDovL3d3dy5kc2Quc3p0YWtpLmh1L35tYXRlL3BtMjA1L2JnLmpwZyI7czo0OiJmb250IjtzOjY6IjAwMDAwMCI7czo0OiJsaW5rIjtzOjY6IjAwMzMwMCI7czo1OiJhbGluayI7czo2OiIwMDY2MDAiO3M6NToidmxpbmsiO3M6NjoiMDA2NjAwIjtzOjM6ImNzcyI7Tjt9czozOiJjc3MiO2I6MDtzOjY6InJhdGluZyI7YjowO3M6NDoiY2hhdCI7YjowO30=', 'f');

INSERT INTO "portal_settings" ("name", "template_id", "password") VALUES ('admin', 1, 'admin');

