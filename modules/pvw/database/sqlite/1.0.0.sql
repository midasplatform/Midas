-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- SQLite database for the pvw module, version 1.0.0

CREATE TABLE IF NOT EXISTS "pvw_instance" (
  "instance_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "item_id" INTEGER NOT NULL,
  "port" INTEGER NOT NULL,
  "pid" INTEGER NOT NULL,
  "sid" TEXT NOT NULL,
  "secret" TEXT NOT NULL,
  "creation_date" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
