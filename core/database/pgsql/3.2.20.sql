-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- PostgreSQL core database, version 3.2.20

SET client_encoding = 'UTF8';
SET default_with_oids = FALSE;

CREATE TABLE IF NOT EXISTS "activedownload" (
    "activedownload_id" serial PRIMARY KEY,
    "ip" character varying(100) DEFAULT ''::character varying NOT NULL,
    "date_creation" timestamp without time zone NOT NULL DEFAULT now(),
    "last_update" timestamp without time zone NOT NULL
);

CREATE INDEX "activedownload_idx_ip" ON "activedownload" ("ip");

CREATE TABLE IF NOT EXISTS "assetstore" (
    "assetstore_id" serial PRIMARY KEY,
    "name" character varying(256) NOT NULL,
    "path" character varying(512) NOT NULL,
    "type" smallint NOT NULL
);

CREATE TABLE IF NOT EXISTS "bitstream" (
    "bitstream_id" serial PRIMARY KEY,
    "itemrevision_id" bigint NOT NULL,
    "name" character varying(256) NOT NULL,
    "mimetype" character varying(30) NOT NULL,
    "sizebytes" bigint NOT NULL,
    "checksum" character varying(64) NOT NULL,
    "path" character varying(512) NOT NULL,
    "assetstore_id" integer NOT NULL,
    "date" timestamp without time zone NOT NULL
);

CREATE INDEX "bitstream_idx_checksum" ON "bitstream" ("checksum");

CREATE TABLE IF NOT EXISTS "community" (
    "community_id" serial PRIMARY KEY,
    "name" character varying(256) NOT NULL,
    "description" text NOT NULL,
    "creation" timestamp without time zone,
    "privacy" integer NOT NULL,
    "folder_id" bigint,
    "admingroup_id" bigint,
    "moderatorgroup_id" bigint,
    "view" bigint DEFAULT 0::bigint NOT NULL,
    "membergroup_id" bigint,
    "can_join" integer DEFAULT 0 NOT NULL,
    "uuid" character varying(512) DEFAULT ''::character varying
);

CREATE TABLE IF NOT EXISTS "communityinvitation" (
    "communityinvitation_id" serial PRIMARY KEY,
    "community_id" bigint,
    "user_id" bigint,
    "group_id" bigint
);

CREATE TABLE IF NOT EXISTS "errorlog" (
    "errorlog_id" serial PRIMARY KEY,
    "priority" integer NOT NULL,
    "module" character varying(256) NOT NULL,
    "message" text NOT NULL,
    "datetime" timestamp without time zone
);

CREATE TABLE IF NOT EXISTS "feed" (
    "feed_id" serial PRIMARY KEY,
    "date" timestamp without time zone NOT NULL,
    "user_id" bigint NOT NULL,
    "type" integer NOT NULL,
    "resource" character varying(256) NOT NULL
);

CREATE TABLE IF NOT EXISTS "feed2community" (
    "id" serial PRIMARY KEY,
    "feed_id" bigint NOT NULL,
    "community_id" bigint NOT NULL
);

CREATE TABLE IF NOT EXISTS "feedpolicygroup" (
    "id" serial PRIMARY KEY,
    "feed_id" bigint NOT NULL,
    "group_id" bigint NOT NULL,
    "policy" smallint NOT NULL,
    "date" timestamp without time zone NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS "feedpolicyuser" (
    "id" serial PRIMARY KEY,
    "feed_id" bigint NOT NULL,
    "user_id" bigint NOT NULL,
    "policy" smallint NOT NULL,
    "date" timestamp without time zone NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS "folder" (
    "folder_id" serial PRIMARY KEY,
    "left_index" bigint NOT NULL,
    "right_index" bigint NOT NULL,
    "parent_id" bigint DEFAULT 0::bigint NOT NULL,
    "name" character varying(256) NOT NULL,
    "description" text NOT NULL,
    "view" bigint DEFAULT 0::bigint NOT NULL,
    "date_update" timestamp without time zone NOT NULL DEFAULT now(),
    "teaser" character varying(256) DEFAULT ''::character varying,
    "privacy_status" integer DEFAULT 0 NOT NULL,
    "uuid" character varying(512) DEFAULT ''::character varying,
    "date_creation" timestamp without time zone
);

CREATE INDEX "folder_idx_left_index" ON "folder" ("left_index");
CREATE INDEX "folder_idx_parent_id" ON "folder" ("parent_id");
CREATE INDEX "folder_idx_right_index" ON "folder" ("right_index");

CREATE TABLE IF NOT EXISTS "folderpolicygroup" (
    "id" serial PRIMARY KEY,
    "folder_id" bigint NOT NULL,
    "group_id" bigint NOT NULL,
    "policy" smallint NOT NULL,
    "date" timestamp without time zone NOT NULL DEFAULT now(),
    UNIQUE ("folder_id", "group_id")
);

CREATE TABLE IF NOT EXISTS "folderpolicyuser" (
    "id" serial PRIMARY KEY,
    "folder_id" bigint NOT NULL,
    "user_id" bigint NOT NULL,
    "policy" smallint NOT NULL,
    "date" timestamp without time zone NOT NULL DEFAULT now(),
    UNIQUE ("folder_id", "user_id")
);

CREATE TABLE IF NOT EXISTS "group" (
    "group_id" serial PRIMARY KEY,
    "community_id" bigint NOT NULL,
    "name" character varying(256) NOT NULL
);

CREATE TABLE IF NOT EXISTS "item" (
    "item_id" serial PRIMARY KEY,
    "name" character varying(256) NOT NULL,
    "date_update" timestamp without time zone NOT NULL DEFAULT now(),
    "description" text NOT NULL,
    "type" integer NOT NULL,
    "view" bigint DEFAULT 0::bigint NOT NULL,
    "download" bigint DEFAULT 0::bigint NOT NULL,
    "sizebytes" bigint DEFAULT 0::bigint NOT NULL,
    "privacy_status" integer DEFAULT 0 NOT NULL,
    "uuid" character varying(512) DEFAULT ''::character varying,
    "date_creation" timestamp without time zone,
    "thumbnail_id" bigint
);

CREATE TABLE IF NOT EXISTS "item2folder" (
    "id" serial PRIMARY KEY,
    "item_id" bigint NOT NULL,
    "folder_id" bigint NOT NULL
);

CREATE INDEX "item2folder_idx_folder_id" ON "item2folder" ("folder_id");

CREATE TABLE IF NOT EXISTS "itempolicygroup" (
    "id" serial PRIMARY KEY,
    "item_id" bigint NOT NULL,
    "group_id" bigint NOT NULL,
    "policy" smallint NOT NULL,
    "date" timestamp without time zone NOT NULL DEFAULT now(),
    UNIQUE ("item_id", "group_id")
);

CREATE TABLE IF NOT EXISTS "itempolicyuser" (
    "id" serial PRIMARY KEY,
    "item_id" bigint NOT NULL,
    "user_id" bigint NOT NULL,
    "policy" smallint NOT NULL,
    "date" timestamp without time zone NOT NULL DEFAULT now(),
    UNIQUE ("item_id", "user_id")
);

CREATE TABLE IF NOT EXISTS "itemrevision" (
    "itemrevision_id" serial PRIMARY KEY,
    "item_id" bigint NOT NULL,
    "revision" integer NOT NULL,
    "date" timestamp without time zone NOT NULL,
    "changes" text NOT NULL,
    "user_id" integer NOT NULL,
    "uuid" character varying(512) DEFAULT ''::character varying,
    "license_id" bigint
);

CREATE TABLE IF NOT EXISTS "license" (
    "license_id" serial PRIMARY KEY,
    "name" text NOT NULL,
    "fulltext" text NOT NULL
);

INSERT INTO "license" VALUES
(1, 'Public (PDDL)', '**You are free:**\n\n* To Share: To copy, distribute and use the database.\n* To Create: To produce works from the database.\n* To Adapt: To modify, transform, and build upon the database.\n\n[Full License Information](http://opendatacommons.org/licenses/pddl/summary)'),
(2, 'Public: Attribution (ODC-BY)', '**You are free:**\n\n* To Share: To copy, distribute and use the database.\n* To Create: To produce works from the database.\n* To Adapt: To modify, transform, and build upon the database.\n\n**As long as you:**\n\n* Attribute: You must attribute any public use of the database, or works produced from the database, in the manner specified in the license. For any use or redistribution of the database, or works produced from it, you must make clear to others the license of the database and keep intact any notices on the original database.\n\n[Full License Information](http://opendatacommons.org/licenses/by/summary)'),
(3, 'Public: Attribution, Share-Alike (ODbL)', '**You are free:**\n\n* To Share: To copy, distribute and use the database.\n* To Create: To produce works from the database.\n* To Adapt: To modify, transform, and build upon the database.\n\n**As long as you:**\n\n* Attribute: You must attribute any public use of the database, or works produced from the database, in the manner specified in the license. For any use or redistribution of the database, or works produced from it, you must make clear to others the license of the database and keep intact any notices on the original database.\n* Share-Alike: If you publicly use any adapted version of this database, or works produced from an adapted database, you must also offer that adapted database under the ODbL.\n* Keep open: If you redistribute the database, or an adapted version of it, then you may use technological measures that restrict the work (such as DRM) as long as you also redistribute a version without such measures.\n\n[Full License Information](http://opendatacommons.org/licenses/odbl/summary)'),
(4, 'Private: All Rights Reserved', 'This work is copyrighted by its author or licensor. You must not share, distribute, or modify this work without the prior consent of the author or licensor.'),
(5, 'Public: Attribution (CC BY 3.0)', '**You are free:**\n\n* To Share: To copy, distribute and transmit the work.\n* To Remix: To adapt the work.\n* To make commercial use of the work.\n\n**Under the following conditions:**\n\n* Attribution: You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).\n\n[Full License Information](http://creativecommons.org/licenses/by/3.0/)'),
(6, 'Public: Attribution, Share-Alike (CC BY-SA 3.0)', '**You are free:**\n\n* To Share: To copy, distribute and transmit the work.\n* To Remix: To adapt the work.\n* To make commercial use of the work.\n\n**Under the following conditions:**\n\n* Attribution: You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).\n* Share-Alike: If you alter, transform, or build upon this work, you may distribute the resulting work only under the same or similar license to this one.\n\n[Full License Information](http://creativecommons.org/licenses/by-sa/3.0/)'),
(7, 'Public: Attribution, No Derivative Works (CC BY-ND 3.0)', '**You are free:**\n\n* To Share: To copy, distribute and transmit the work.\n* To make commercial use of the work.\n\n**Under the following conditions:**\n\n* Attribution: You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).\n* No Derivative Works: You may not alter, transform, or build upon this work.\n\n[Full License Information](http://creativecommons.org/licenses/by-nd/3.0/)'),
(8, 'Public: Attribution, Non-Commercial (CC BY-NC 3.0)', '**You are free:**\n\n* To Share: To copy, distribute and transmit the work.\n* To Remix: To adapt the work.\n\n**Under the following conditions:**\n\n* Attribution: You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).\n* Non-Commercial: You may not use this work for commercial purposes.\n\n[Full License Information](http://creativecommons.org/licenses/by-nc/3.0/)'),
(9, 'Public: Attribution, Non-Commercial, Share-Alike (CC BY-NC-SA 3.0)', '**You are free:**\n\n* To Share: To copy, distribute and transmit the work.\n* To Remix: To adapt the work.\n\n**Under the following conditions:**\n\n* Attribution: You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).\n* Non-Commercial: You may not use this work for commercial purposes.\n* Share-Alike: If you alter, transform, or build upon this work, you may distribute the resulting work only under the same or similar license to this one.\n\n[Full License Information](http://creativecommons.org/licenses/by-nc-sa/3.0/)'),
(10, 'Public: Attribution, Non-Commercial, No Derivative Works (CC BY-NC-ND 3.0)', '**You are free:**\n\n* To Share: To copy, distribute and transmit the work.\n\n**Under the following conditions:**\n\n* Attribution: You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).\n* Non-Commercial: You may not use this work for commercial purposes.\n* No Derivative Works: You may not alter, transform, or build upon this work.\n\n[Full License Information](http://creativecommons.org/licenses/by-nc-nd/3.0/)');

CREATE TABLE IF NOT EXISTS "metadata" (
    "metadata_id" serial PRIMARY KEY,
    "metadatatype" integer DEFAULT 0 NOT NULL,
    "element" character varying(256) NOT NULL,
    "qualifier" character varying(256) NOT NULL
);

INSERT INTO metadata VALUES
(1, 0, 'contributor', 'author'),
(2, 0, 'date', 'uploaded'),
(3, 0, 'date', 'issued'),
(4, 0, 'date', 'created'),
(5, 0, 'identifier', 'citation'),
(6, 0, 'identifier', 'uri'),
(7, 0, 'identifier', 'pubmed'),
(8, 0, 'identifier', 'doi'),
(9, 0, 'description', 'general'),
(10, 0, 'description', 'provenance'),
(11, 0, 'description', 'sponsorship'),
(12, 0, 'description', 'publisher'),
(13, 0, 'subject', 'keyword'),
(14, 0, 'subject', 'ocis');

CREATE TABLE IF NOT EXISTS "metadatavalue" (
    "metadatavalue_id" serial PRIMARY KEY,
    "metadata_id" bigint NOT NULL,
    "itemrevision_id" bigint NOT NULL,
    "value" character varying(1024) NOT NULL
);

CREATE TABLE IF NOT EXISTS "newuserinvitation" (
    "newuserinvitation_id" serial PRIMARY KEY,
    "auth_key" character varying(256) NOT NULL,
    "email" character varying(256) NOT NULL,
    "inviter_id" bigint NOT NULL,
    "community_id" bigint NOT NULL,
    "group_id" bigint NOT NULL,
    "date_creation" timestamp without time zone NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS "password" (
    "hash" character varying(128) NOT NULL,
    CONSTRAINT "password_hash" PRIMARY KEY ("hash")
);

ALTER TABLE "password" CLUSTER ON "password_hash";

CREATE TABLE IF NOT EXISTS "pendinguser" (
    "pendinguser_id" serial PRIMARY KEY,
    "auth_key" character varying(256) NOT NULL,
    "email" character varying(256) NOT NULL,
    "firstname" character varying(256) NOT NULL,
    "lastname" character varying(256) NOT NULL,
    "date_creation" timestamp without time zone NOT NULL DEFAULT now(),
    "salt" character varying(64) DEFAULT ''::character varying NOT NULL
);

CREATE TABLE IF NOT EXISTS "progress" (
    "progress_id" serial PRIMARY KEY,
    "message" text NOT NULL,
    "current" bigint NOT NULL,
    "maximum" bigint NOT NULL,
    "date_creation" timestamp without time zone NOT NULL DEFAULT now(),
    "last_update" timestamp without time zone NOT NULL
);

CREATE TABLE IF NOT EXISTS "setting" (
    "setting_id" serial PRIMARY KEY,
    "module" character varying(256) NOT NULL,
    "name" character varying(256) NOT NULL,
    "value" text NOT NULL
);

CREATE TABLE IF NOT EXISTS "token" (
    "token_id" serial PRIMARY KEY,
    "userapi_id" bigint NOT NULL,
    "token" character varying(64) NOT NULL,
    "expiration_date" timestamp without time zone NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS "user" (
    "user_id" serial PRIMARY KEY,
    "firstname" character varying(256) NOT NULL,
    "company" character varying(256),
    "thumbnail" character varying(256),
    "lastname" character varying(256) NOT NULL,
    "email" character varying(256) NOT NULL,
    "privacy" integer DEFAULT 0 NOT NULL,
    "admin" integer DEFAULT 0 NOT NULL,
    "view" bigint DEFAULT 0::bigint NOT NULL,
    "folder_id" bigint,
    "creation" timestamp without time zone,
    "uuid" character varying(512) DEFAULT ''::character varying,
    "city" character varying(100) DEFAULT ''::character varying,
    "country" character varying(100) DEFAULT ''::character varying,
    "website" character varying(256) DEFAULT ''::character varying,
    "biography" text,
    "dynamichelp" integer DEFAULT 1,
    "hash_alg" character varying(32) DEFAULT ''::character varying NOT NULL,
    "salt" character varying(64) DEFAULT ''::character varying NOT NULL
);

CREATE TABLE IF NOT EXISTS "user2group" (
    "id" serial PRIMARY KEY,
    "user_id" bigint NOT NULL,
    "group_id" bigint NOT NULL
);

CREATE TABLE IF NOT EXISTS "userapi" (
    "userapi_id" serial PRIMARY KEY,
    "user_id" bigint NOT NULL,
    "apikey" character varying(64) NOT NULL,
    "application_name" character varying(256) NOT NULL,
    "token_expiration_time" integer NOT NULL,
    "creation_date" timestamp without time zone
);
