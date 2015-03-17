-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- SQLite database for the dicomserver module, version 1.1.0

CREATE TABLE IF NOT EXISTS "dicomserver_registration" (
  "registration_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "item_id" INTEGER NOT NULL
);
