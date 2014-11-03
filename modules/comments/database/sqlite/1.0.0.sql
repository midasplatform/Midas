-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- SQLite database for the comments module, version 1.0.0

CREATE TABLE IF NOT EXISTS "comments_item" (
  "comment_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "item_id" INTEGER NOT NULL,
  "user_id" INTEGER NOT NULL,
  "comment" TEXT NOT NULL,
  "date" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "comments_item_item_id_idx" ON "comments_item" ("item_id");
CREATE INDEX IF NOT EXISTS "comments_item_user_id_idx" ON "comments_item" ("user_id");
