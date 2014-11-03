-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- PostgreSQL database for the thumbnailcreator module, version 1.0.0

SET client_encoding = 'UTF8';
SET default_with_oids = FALSE;

CREATE TABLE IF NOT EXISTS "thumbnailcreator_itemthumbnail" (
    "itemthumbnail_id" serial PRIMARY KEY,
    "item_id" integer,
    "thumbnail" character varying(255)
);

CREATE INDEX "thumbnailcreator_itemthumbnail_item_id" ON "thumbnailcreator_itemthumbnail" ("item_id");
