-- -*- tab-width: 2; indent-tabs-mode: 1; -*-

--	 $Id: constraints.sql 551 2006-04-06 16:40:43Z micsik $
--
-- Created for the StreamOnTheFly project by Andras Micsik
-- Ezecute this after any migration/upgrade of the SQL database, 
-- because foreign keys seem to disappear sometimes

ALTER TABLE "sotf_blobs" ADD CONSTRAINT "to_node_objects" FOREIGN KEY("id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_blobs" ADD CONSTRAINT "to_objects" FOREIGN KEY("object_id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_nodes" ADD CONSTRAINT "to_node_objects" FOREIGN KEY("id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_user_permissions" ADD CONSTRAINT "to_permissions" FOREIGN KEY("permission_id") REFERENCES sotf_permissions(id) ON DELETE CASCADE;

ALTER TABLE "sotf_stations" ADD CONSTRAINT "to_node_objects" FOREIGN KEY("id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_contacts" ADD CONSTRAINT "to_node_objects" FOREIGN KEY("id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_contacts" ADD CONSTRAINT "to_stations" FOREIGN KEY("station_id") REFERENCES sotf_stations(id) ON DELETE CASCADE;

ALTER TABLE "sotf_series" ADD CONSTRAINT "to_node_objects" FOREIGN KEY("id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_series" ADD CONSTRAINT "to_stations" FOREIGN KEY("station_id") REFERENCES sotf_stations("id") ON DELETE CASCADE;

ALTER TABLE "sotf_object_roles" ADD  CONSTRAINT "sotf_roles_uniq" UNIQUE("object_id", "contact_id", "role_id");

ALTER TABLE "sotf_object_roles" ADD CONSTRAINT "to_node_objects" FOREIGN KEY("id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_object_roles" ADD CONSTRAINT "to_contacts" FOREIGN KEY("contact_id") REFERENCES sotf_contacts("id") ON DELETE CASCADE;

ALTER TABLE "sotf_object_roles" ADD CONSTRAINT "to_objects" FOREIGN KEY("object_id") REFERENCES sotf_node_objects("id") ON DELETE CASCADE;

ALTER TABLE "sotf_programmes" ADD CONSTRAINT "to_node_objects" FOREIGN KEY("id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_programmes" ADD CONSTRAINT "to_stations" FOREIGN KEY("station_id") REFERENCES sotf_stations("id") ON DELETE CASCADE;

ALTER TABLE "sotf_programmes" ADD CONSTRAINT "to_series" FOREIGN KEY("series_id") REFERENCES sotf_series("id") ON DELETE CASCADE;

ALTER TABLE "sotf_rights" ADD CONSTRAINT "to_node_objects" FOREIGN KEY("id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_rights" ADD CONSTRAINT "to_progs" FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE;

ALTER TABLE "sotf_extradata" ADD CONSTRAINT "to_node_objects" FOREIGN KEY("id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_extradata" ADD CONSTRAINT "to_progs" FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE;

ALTER TABLE "sotf_other_files" ADD CONSTRAINT "to_node_objects" FOREIGN KEY("id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_other_files" ADD CONSTRAINT "to_progs" FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE;

ALTER TABLE "sotf_media_files" ADD CONSTRAINT "to_node_objects" FOREIGN KEY("id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_media_files" ADD CONSTRAINT "to_progs" FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE;

ALTER TABLE "sotf_links" ADD CONSTRAINT "to_node_objects" FOREIGN KEY("id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_links" ADD CONSTRAINT "to_progs" FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE
;

ALTER TABLE "sotf_topic_tree_defs" ADD CONSTRAINT "to_node_objects" FOREIGN KEY("id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_topic_trees" ADD CONSTRAINT "to_node_objects" FOREIGN KEY("id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_topics" ADD CONSTRAINT "to_node_objects" FOREIGN KEY("id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_topics" ADD CONSTRAINT "to_topics" FOREIGN KEY("topic_id") REFERENCES sotf_topic_tree_defs("id") ON DELETE CASCADE;

ALTER TABLE "sotf_prog_topics" ADD CONSTRAINT "to_node_objects" FOREIGN KEY("id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_prog_topics" ADD CONSTRAINT "to_progs" FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE;

ALTER TABLE "sotf_genres" ADD CONSTRAINT "to_node_objects" FOREIGN KEY("id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_roles" ADD CONSTRAINT "to_node_objects" FOREIGN KEY("id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_role_names" ADD CONSTRAINT "to_node_objects" FOREIGN KEY("id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_role_names" ADD CONSTRAINT "to_roles" FOREIGN KEY("role_id") REFERENCES sotf_roles(role_id) ON DELETE CASCADE;

ALTER TABLE "sotf_deletions" ADD CONSTRAINT "to_node_objects" FOREIGN KEY("id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_playlists" ADD CONSTRAINT "to_progs" FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE;

ALTER TABLE "sotf_ratings" ADD CONSTRAINT "to_progs" FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE;

ALTER TABLE "sotf_prog_rating" ADD CONSTRAINT "to_node_objects" FOREIGN KEY("id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_prog_rating" ADD CONSTRAINT "to_progs" FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE;

ALTER TABLE "sotf_prog_refs" ADD CONSTRAINT "to_node_objects" FOREIGN KEY("id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_prog_refs" ADD CONSTRAINT "to_progs" FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE;

ALTER TABLE "sotf_prog_stats" ADD CONSTRAINT "to_node_objects" FOREIGN KEY("id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_prog_stats" ADD CONSTRAINT "to_progs" FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE;

ALTER TABLE "sotf_prog_stats" ADD CONSTRAINT "to_stations" FOREIGN KEY("station_id") REFERENCES sotf_stations("id") ON DELETE CASCADE;

ALTER TABLE "sotf_stats" ADD CONSTRAINT "to_progs" FOREIGN KEY("prog_id") REFERENCES sotf_programmes("id") ON DELETE CASCADE;

ALTER TABLE "sotf_stats" ADD CONSTRAINT "to_stations" FOREIGN KEY("station_id") REFERENCES sotf_stations("id") ON DELETE CASCADE;

ALTER TABLE "sotf_comments" ADD CONSTRAINT "to_progs" FOREIGN KEY("prog_id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_to_forward" ADD CONSTRAINT "to_progs" FOREIGN KEY("prog_id") REFERENCES sotf_programmes(id) ON DELETE CASCADE;

ALTER TABLE "sotf_unique_access" ADD CONSTRAINT "to_progs" FOREIGN KEY("prog_id") REFERENCES sotf_programmes(id) ON DELETE CASCADE;

ALTER TABLE "sotf_user_progs" ADD CONSTRAINT "to_progs" FOREIGN KEY("prog_id") REFERENCES sotf_programmes(id) ON DELETE CASCADE;

ALTER TABLE "sotf_portals" ADD CONSTRAINT "to_node_objects" FOREIGN KEY("id") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_station_mappings" ADD CONSTRAINT "to_node_objects" FOREIGN KEY("id_at_node") REFERENCES sotf_node_objects(id) ON DELETE CASCADE;

ALTER TABLE "sotf_station_mappings" ADD CONSTRAINT "to_station" FOREIGN KEY("station") REFERENCES sotf_stations(id) ON DELETE CASCADE;
