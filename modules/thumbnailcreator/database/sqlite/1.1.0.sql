-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- SQLite database for the thumbnailcreator module, version 1.1.0

CREATE TABLE IF NOT EXISTS "thumbnailcreator_itemthumbnail" (
  "itemthumbnail_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "item_id" INTEGER,
  "thumbnail_id" INTEGER
);

CREATE INDEX IF NOT EXISTS "thumbnailcreator_itemthumbnail_item_id_idx" ON "thumbnailcreator_itemthumbnail" ("item_id");
