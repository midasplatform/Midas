-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- SQLite database for the mfa module, version 1.0.0

CREATE TABLE IF NOT EXISTS "mfa_otpdevice" (
  "otpdevice_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "user_id" INTEGER NOT NULL,
  "secret" TEXT NOT NULL,
  "algorithm" TEXT NOT NULL,
  "counter" TEXT NOT NULL DEFAULT '',
  "length" INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS "mfa_apitoken" (
  "apitoken_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "user_id" INTEGER NOT NULL,
  "token_id" INTEGER NOT NULL,
  "creation_date" TEXT NOT NULL
);
