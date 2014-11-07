-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- PostgreSQL database for the thumbnailcreator module, version 1.0.3

SET client_encoding = 'UTF8';
SET default_with_oids = FALSE;

CREATE TABLE IF NOT EXISTS "thumbnailcreator_itemthumbnail" (
    "itemthumbnail_id" serial PRIMARY KEY,
    "item_id" integer,
    "thumbnail_id" bigint
);

CREATE INDEX "thumbnailcreator_itemthumbnail_item_id" ON "thumbnailcreator_itemthumbnail" ("item_id");

INSERT INTO "setting" ("module", "name", "value") VALUES
('thumbnailcreator', 'provider', 'gd'),
('thumbnailcreator', 'format', 'jpg'),
('thumbnailcreator', 'image_magick', ''),
('thumbnailcreator', 'use_thumbnailer', 0),
('thumbnailcreator', 'thumbnailer', '');
