-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- SQLite database for the googleauth module, version 1.1.0

CREATE TABLE IF NOT EXISTS "googleauth_user" (
  "googleauth_user_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "google_person_id" TEXT NOT NULL,
  "user_id" INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS "googleauth_user_google_person_id_idx" ON "googleauth_user" ("google_person_id");
CREATE INDEX IF NOT EXISTS "googleauth_user_user_id_idx" ON "googleauth_user" ("user_id");
