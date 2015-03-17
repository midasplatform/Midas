-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- SQLite database for the landingpage module, version 1.0.0

CREATE TABLE IF NOT EXISTS "landingpage_text" (
  "landingpage_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "text" TEXT 
);
