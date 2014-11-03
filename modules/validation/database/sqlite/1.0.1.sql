-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- SQLite database for the validation module, version 1.0.1

CREATE TABLE IF NOT EXISTS "validation_dashboard" (
  "dashboard_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "owner_id" INTEGER NOT NULL DEFAULT 0,
  "name" TEXT NOT NULL,
  "description" TEXT NOT NULL,
  "truthfolder_id" INTEGER,
  "testingfolder_id" INTEGER,
  "trainingfolder_id" INTEGER,
  "min" REAL,
  "max" REAL,
  "metric_id" INTEGER
);

CREATE TABLE IF NOT EXISTS "validation_dashboard2folder" (
  "dashboard2folder_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "dashboard_id" INTEGER NOT NULL,
  "folder_id" INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS "validation_dashboard2scalarresult" (
  "dashboard2scalarresult_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "dashboard_id" INTEGER NOT NULL,
  "scalarresult_id" INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS "validation_scalarresult" (
  "scalarresult_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "folder_id" INTEGER NOT NULL,
  "item_id" INTEGER NOT NULL,
  "value" REAL
);
