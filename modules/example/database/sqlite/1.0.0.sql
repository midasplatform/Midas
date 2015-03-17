-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- SQLite database for the example module, version 1.0.0

CREATE TABLE IF NOT EXISTS "example_wallet" (
  "example_wallet_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "user_id" INTEGER NOT NULL,
  "dollars" INTEGER NOT NULL
);
