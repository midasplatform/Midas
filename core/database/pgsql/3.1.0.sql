-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- PostgreSQL core database, version 3.1.0

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
    "creation" timestamp without time zone,
    "privacy" integer NOT NULL,
    "folder_id" bigint,
    "publicfolder_id" bigint,
    "privatefolder_id" bigint,
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
    "user_id" bigint
);

CREATE TABLE IF NOT EXISTS "errorlog" (
    "errorlog_id_id" serial PRIMARY KEY,
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
    "date" timestamp without time zone DEFAULT now()
);

CREATE TABLE IF NOT EXISTS "feedpolicyuser" (
    "id" serial PRIMARY KEY,
    "feed_id" bigint NOT NULL,
    "user_id" bigint NOT NULL,
    "policy" smallint NOT NULL,
    "date" timestamp without time zone DEFAULT now()
);

CREATE TABLE IF NOT EXISTS "folder" (
    "folder_id" serial PRIMARY KEY,
    "left_indice" bigint NOT NULL,
    "right_indice" bigint NOT NULL,
    "parent_id" bigint DEFAULT 0::bigint NOT NULL,
    "name" character varying(256) NOT NULL,
    "description" text NOT NULL,
    "view" bigint DEFAULT 0::bigint NOT NULL,
    "date_update" timestamp without time zone DEFAULT now(),
    "teaser" character varying(250) DEFAULT ''::character varying,
    "privacy_status" integer DEFAULT 0 NOT NULL,
    "uuid" character varying(512) DEFAULT ''::character varying,
    "date_creation" timestamp without time zone
);

CREATE TABLE IF NOT EXISTS "folderpolicygroup" (
    "id" serial PRIMARY KEY,
    "folder_id" bigint NOT NULL,
    "group_id" bigint NOT NULL,
    "policy" smallint NOT NULL,
    "date" timestamp without time zone DEFAULT now()
);

CREATE TABLE IF NOT EXISTS "folderpolicyuser" (
    "id" serial PRIMARY KEY,
    "folder_id" bigint NOT NULL,
    "user_id" bigint NOT NULL,
    "policy" smallint NOT NULL,
    "date" timestamp without time zone DEFAULT now()
);

CREATE TABLE IF NOT EXISTS "group" (
    "group_id" serial PRIMARY KEY,
    "community_id" bigint NOT NULL,
    "name" character varying(256) NOT NULL
);

CREATE TABLE IF NOT EXISTS "item" (
    "item_id" serial PRIMARY KEY,
    "name" character varying(250) NOT NULL,
    "date_update" timestamp without time zone DEFAULT now(),
    "description" character varying(20) NOT NULL,
    "type" integer NOT NULL,
    "view" bigint DEFAULT 0::bigint NOT NULL,
    "download" bigint DEFAULT 0::bigint NOT NULL,
    "thumbnail" character varying(256),
    "sizebytes" bigint DEFAULT 0::bigint NOT NULL,
    "privacy_status" integer DEFAULT 0 NOT NULL,
    "uuid" character varying(512) DEFAULT ''::character varying,
    "date_creation" timestamp without time zone
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

CREATE TABLE IF NOT EXISTS "itemkeyword" (
    "keyword_id" serial PRIMARY KEY,
    "value" character varying(256) NOT NULL,
    "relevance" integer NOT NULL
);

CREATE TABLE IF NOT EXISTS "itempolicygroup" (
    "id" serial PRIMARY KEY,
    "item_id" bigint NOT NULL,
    "group_id" bigint NOT NULL,
    "policy" smallint NOT NULL,
    "date" timestamp without time zone DEFAULT now()
);

CREATE TABLE IF NOT EXISTS "itempolicyuser" (
    "id" serial PRIMARY KEY,
    "item_id" bigint NOT NULL,
    "user_id" bigint NOT NULL,
    "policy" smallint NOT NULL,
    "date" timestamp without time zone DEFAULT now()
);

CREATE TABLE IF NOT EXISTS "itemrevision" (
    "itemrevision_id" serial PRIMARY KEY,
    "item_id" bigint NOT NULL,
    "revision" integer NOT NULL,
    "date" timestamp without time zone NOT NULL,
    "changes" text NOT NULL,
    "user_id" integer NOT NULL,
    "license" integer DEFAULT 0 NOT NULL,
    "uuid" character varying(512) DEFAULT ''::character varying
);

CREATE TABLE IF NOT EXISTS "metadata" (
    "metadata_id" serial PRIMARY KEY,
    "metadatatype" integer DEFAULT 0 NOT NULL,
    "element" character varying(256) NOT NULL,
    "qualifier" character varying(256) NOT NULL,
    "description" character varying(512) NOT NULL
);

CREATE TABLE IF NOT EXISTS "metadatadocumentvalue" (
    "id" serial PRIMARY KEY,
    "metadata_id" bigint NOT NULL,
    "itemrevision_id" bigint NOT NULL,
    "value" character varying(1024) NOT NULL,
    "metadatavalue_id" integer NOT NULL
);

CREATE TABLE IF NOT EXISTS "metadatavalue" (
    "id" serial PRIMARY KEY,
    "metadata_id" bigint NOT NULL,
    "itemrevision_id" bigint NOT NULL,
    "value" character varying(1024) NOT NULL,
    "metadatavalue_id" integer NOT NULL
);

INSERT INTO "metadata" ("metadatatype", "element", "qualifier", "description")
VALUES ('0', 'contributor', 'author', 'Author of the data');
INSERT INTO "metadata" ("metadatatype", "element", "qualifier", "description")
VALUES ('0', 'date', 'uploaded', 'Date when the data was uploaded');
INSERT INTO "metadata" ("metadatatype", "element", "qualifier", "description")
VALUES ('0', 'date', 'issued', 'Date when the data was released');
INSERT INTO "metadata" ("metadatatype", "element", "qualifier", "description")
VALUES ('0', 'date', 'created', 'Date when the data was created');
INSERT INTO "metadata" ("metadatatype", "element", "qualifier", "description")
VALUES ('0', 'identifier', 'citation', 'Citation of the data');
INSERT INTO "metadata" ("metadatatype", "element", "qualifier", "description")
VALUES ('0', 'identifier', 'uri', 'URI identifier');
INSERT INTO "metadata" ("metadatatype", "element", "qualifier", "description")
VALUES ('0', 'identifier', 'pubmed', 'PubMed identifier');
INSERT INTO "metadata" ("metadatatype", "element", "qualifier", "description")
VALUES ('0', 'identifier', 'doi', 'Digital Object Identifier');
INSERT INTO "metadata" ("metadatatype", "element", "qualifier", "description")
VALUES ('0', 'description', 'general', 'General description field');
INSERT INTO "metadata" ("metadatatype", "element", "qualifier", "description")
VALUES ('0', 'description', 'provenance', 'Provenance of the data');
INSERT INTO "metadata" ("metadatatype", "element", "qualifier", "description")
VALUES ('0', 'description', 'sponsorship', 'Sponsor of the data');
INSERT INTO "metadata" ("metadatatype", "element", "qualifier", "description")
VALUES ('0', 'description', 'publisher', 'Publisher of the data');
INSERT INTO "metadata" ("metadatatype", "element", "qualifier", "description")
VALUES ('0', 'subject', 'keyword', 'Keyword');
INSERT INTO "metadata" ("metadatatype", "element", "qualifier", "description")
VALUES ('0', 'subject', 'ocis', 'OCIS subject');

CREATE TABLE IF NOT EXISTS "user" (
    "user_id" serial PRIMARY KEY,
    "password" character varying(100) NOT NULL,
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
    "publicfolder_id" bigint,
    "privatefolder_id" bigint,
    "uuid" character varying(512) DEFAULT ''::character varying,
    "city" character varying(100) DEFAULT ''::character varying,
    "country" character varying(100) DEFAULT ''::character varying,
    "website" character varying(255) DEFAULT ''::character varying,
    "biography" character varying(255) DEFAULT ''::character varying
);

CREATE TABLE IF NOT EXISTS "user2group" (
    "id" serial PRIMARY KEY,
    "user_id" bigint NOT NULL,
    "group_id" bigint NOT NULL
);
