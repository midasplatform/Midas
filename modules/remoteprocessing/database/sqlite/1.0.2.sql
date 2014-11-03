-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- SQLite database for the remoteprocessing module, version 1.0.2

CREATE TABLE IF NOT EXISTS "remoteprocessing_job" (
  "job_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "os" TEXT NOT NULL,
  "condition" TEXT NOT NULL DEFAULT '',
  script text,
  params text,
  "status" INTEGER NOT NULL DEFAULT 0,
  "expiration_date" TEXT,
  "creation_date" TEXT,
  "start_date" TEXT,
  "creator_id" INTEGER,
  "name" TEXT
);

CREATE TABLE IF NOT EXISTS "remoteprocessing_job2item" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "job_id" INTEGER NOT NULL,
  "item_id" INTEGER NOT NULL,
  "type" INTEGER NOT NULL DEFAULT 0
);
