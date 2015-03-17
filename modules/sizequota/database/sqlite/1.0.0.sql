-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- SQLite database for the sizequota module, version 1.0.0

CREATE TABLE IF NOT EXISTS "sizequota_folderquota" (
  "folderquota_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "folder_id" INTEGER NOT NULL,
  "quota" TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS "sizequota_folderquota_folder_id_idx" ON "sizequota_folderquota" ("folder_id");
