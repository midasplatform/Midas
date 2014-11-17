-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- SQLite database for the statistics module, version 1.1.0

CREATE TABLE IF NOT EXISTS "statistics_download" (
  "download_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "item_id" INTEGER NOT NULL,
  "user_id" INTEGER,
  "date" TEXT,
  "user_agent" TEXT,
  "ip_location_id" INTEGER
);

CREATE INDEX IF NOT EXISTS "statistics_download_item_id_idx" ON "statistics_download" ("item_id");

CREATE TABLE IF NOT EXISTS "statistics_ip_location" (
  "ip_location_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "ip" TEXT UNIQUE,
  "latitude" TEXT,
  "longitude" TEXT
);

CREATE INDEX IF NOT EXISTS "statistics_ip_location_ip_idx" ON "statistics_ip_location" ("ip");
CREATE INDEX IF NOT EXISTS "statistics_ip_location_latitude_idx" ON "statistics_ip_location" ("latitude");
