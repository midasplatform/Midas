-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- SQLite database for the ratings module, version 1.0.0

CREATE TABLE IF NOT EXISTS "ratings_item" (
  "rating_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "item_id" INTEGER NOT NULL,
  "user_id" INTEGER NOT NULL,
  "rating" INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS "ratings_item_item_id_idx" ON "ratings_item" ("item_id");
CREATE INDEX IF NOT EXISTS "ratings_item_user_id_idx" ON "ratings_item" ("user_id");
