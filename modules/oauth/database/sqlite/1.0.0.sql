-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- SQLite database for the oauth module, version 1.0.0

CREATE TABLE IF NOT EXISTS "oauth_client" (
  "client_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "name" TEXT NOT NULL DEFAULT '',
  "secret" TEXT NOT NULL,
  "owner_id" INTEGER NOT NULL,
  "creation_date" TEXT
);

CREATE INDEX IF NOT EXISTS "oauth_client_owner_id_idx" ON "oauth_client" ("owner_id");

CREATE TABLE IF NOT EXISTS "oauth_code" (
  "code_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "code" TEXT NOT NULL,
  "scopes" TEXT NOT NULL,
  "user_id" INTEGER NOT NULL,
  "client_id" INTEGER NOT NULL,
  "creation_date" TEXT,
  "expiration_date" TEXT
);

CREATE INDEX IF NOT EXISTS "oauth_code_code_idx" ON "oauth_code" ("code");

CREATE TABLE IF NOT EXISTS "oauth_token" (
  "token_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "client_id" INTEGER NOT NULL,
  "user_id" INTEGER NOT NULL,
  "token" TEXT NOT NULL,
  "scopes" TEXT NOT NULL,
  "type" INTEGER NOT NULL,
  "creation_date" TEXT,
  "expiration_date" TEXT
);

CREATE INDEX IF NOT EXISTS "oauth_token_token_idx" ON "oauth_token" ("token");
CREATE INDEX IF NOT EXISTS "oauth_token_user_id_idx" ON "oauth_token" ("user_id");
