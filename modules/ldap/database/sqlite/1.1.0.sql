-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- SQLite database for the ldap module, version 1.1.0

CREATE TABLE IF NOT EXISTS "ldap_user" (
  "ldap_user_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "user_id" INTEGER NOT NULL,
  "login" TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS "ldap_user_login_idx" ON "ldap_user" ("login");
