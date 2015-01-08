-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- SQLite database for the @MN@ module, version 1.0.0

CREATE TABLE IF NOT EXISTS "@MN@_thing" (
  "thing_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "creation_date" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
