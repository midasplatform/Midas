-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- PostgreSQL core database, version 3.0.1

SET client_encoding = 'UTF8';
SET default_with_oids = FALSE;

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

CREATE TABLE IF NOT EXISTS "community" (
    "community_id" serial PRIMARY KEY,
    "name" character varying(256) NOT NULL,
    "description" text NOT NULL,
    "creation" timestamp without time zone NULL DEFAULT NULL,
    "privacy" integer NOT NULL,
    "folder_id" bigint NOT NULL,
    "publicfolder_id" bigint NOT NULL,
    "privatefolder_id" bigint NOT NULL,
    "admingroup_id" bigint NOT NULL,
    "moderatorgroup_id" bigint NOT NULL,
    "view" bigint NOT NULL DEFAULT 0::bigint,
    "membergroup_id" bigint NOT NULL

);

CREATE TABLE IF NOT EXISTS "errorlog" (
    "errorlog_id_id" serial PRIMARY KEY,
    "priority" integer NOT NULL,
    "module" character varying(256) NOT NULL,
    "message" text NOT NULL,
    "datetime" timestamp without time zone NULL DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS "feed" (
    "feed_id" serial PRIMARY KEY,
    "date" timestamp without time zone NOT NULL,
    "user_id" bigint NOT NULL,
    "type" integer NOT NULL,
    "ressource" character varying(256) NOT NULL
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
    "date" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "feedpolicyuser" (
    "id" serial PRIMARY KEY,
    "feed_id" bigint NOT NULL,
    "user_id" bigint NOT NULL,
    "policy" smallint NOT NULL,
    "date" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "folder" (
    "folder_id" serial PRIMARY KEY,
    "left_indice" bigint NOT NULL,
    "right_indice" bigint NOT NULL,
    "parent_id" bigint NOT NULL DEFAULT 0::bigint,
    "name" character varying(256) NOT NULL,
    "description" text NOT NULL,
    "view" bigint NOT NULL DEFAULT 0::bigint,
    "date" timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "folderpolicygroup" (
    "id" serial PRIMARY KEY,
    "folder_id" bigint NOT NULL,
    "group_id" bigint NOT NULL,
    "policy" smallint NOT NULL,
    "date" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "folderpolicyuser" (
    "id" serial PRIMARY KEY,
    "folder_id" bigint NOT NULL,
    "user_id" bigint NOT NULL,
    "policy" smallint NOT NULL,
    "date" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "group" (
    "group_id" serial PRIMARY KEY,
    "community_id" bigint NOT NULL,
    "name" character varying(256) NOT NULL
);

INSERT INTO "group" ("group_id", "community_id", "name") VALUES (0, 0, 'Anonymous');

CREATE TABLE IF NOT EXISTS "item" (
    "item_id" serial PRIMARY KEY,
    "name" character varying(250) NOT NULL,
    "date" timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    "description" character varying(20) NOT NULL,
    "type" integer NOT NULL,
    "view" bigint NOT NULL DEFAULT 0::bigint,
    "download" bigint NOT NULL DEFAULT 0::bigint,
    "thumbnail" character varying(256),
    "sizebytes" bigint NOT NULL DEFAULT 0::bigint
);

CREATE TABLE IF NOT EXISTS "itempolicygroup" (
    "id" serial PRIMARY KEY,
    "item_id" bigint NOT NULL,
    "group_id" bigint NOT NULL,
    "policy" smallint NOT NULL,
    "date" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "itempolicyuser" (
    "id" serial PRIMARY KEY,
    "item_id" bigint NOT NULL,
    "user_id" bigint NOT NULL,
    "policy" smallint NOT NULL,
    "date" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "item2folder" (
    "id" serial PRIMARY KEY,
    "item_id" bigint NOT NULL,
    "folder_id" bigint NOT NULL
);

CREATE TABLE IF NOT EXISTS "item2keyword" (
    "id" serial PRIMARY KEY,
    "item_id" bigint NOT NULL,
    "keyword_id" bigint NOT NULL
);

CREATE TABLE IF NOT EXISTS "itemrevision" (
    "itemrevision_id" serial PRIMARY KEY,
    "item_id" bigint NOT NULL,
    "revision" integer NOT NULL,
    "date" timestamp without time zone NOT NULL,
    "changes" text NOT NULL,
    "user_id" integer NOT NULL
);

CREATE TABLE IF NOT EXISTS "itemkeyword" (
    "keyword_id" serial PRIMARY KEY,
    "value" character varying(256) NOT NULL,
    "relevance" integer NOT NULL
);

CREATE TABLE IF NOT EXISTS "metadata" (
    "metadata_id" serial PRIMARY KEY,
    "metadatatype_id" integer NOT NULL,
    "element" character varying(256) NOT NULL,
    "qualifier" character varying(256) NOT NULL,
    "description" character varying(512) NOT NULL
);

CREATE TABLE IF NOT EXISTS "metadatadocumentvalue" (
    "id" serial PRIMARY KEY,
    "metadata_id" bigint NOT NULL,
    "itemrevision_id" bigint NOT NULL,
    "value" character varying(1024) NOT NULL
);

CREATE TABLE IF NOT EXISTS "metadatatype" (
    "metadatatype_id" serial PRIMARY KEY,
    "name" character varying(256) NOT NULL
);

CREATE TABLE IF NOT EXISTS "metadatavalue" (
    "id" serial PRIMARY KEY,
    "metadata_id" bigint NOT NULL,
    "itemrevision_id" bigint NOT NULL,
    "value" character varying(1024) NOT NULL
);

CREATE TABLE IF NOT EXISTS "user" (
    "user_id" serial PRIMARY KEY,
    "password" character varying(100) NOT NULL,
    "firstname" character varying(256) NOT NULL,
    "company" character varying(256),
    "thumbnail" character varying(256),
    "lastname" character varying(256) NOT NULL,
    "email" character varying(256) NOT NULL,
    "privacy" integer NOT NULL DEFAULT 0,
    "admin" integer NOT NULL DEFAULT 0,
    "view" bigint NOT NULL DEFAULT 0::bigint,
    "folder_id" bigint,
    "creation" timestamp without time zone NULL DEFAULT NULL,
    "publicfolder_id" bigint,
    "privatefolder_id" bigint
);

CREATE TABLE IF NOT EXISTS "user2group" (
    "id" serial PRIMARY KEY,
    "user_id" bigint NOT NULL,
    "group_id" bigint NOT NULL
);
