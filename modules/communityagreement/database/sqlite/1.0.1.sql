-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- SQLite database for the communityagreement module, version 1.0.1

CREATE TABLE IF NOT EXISTS "communityagreement_agreement" (
  "agreement_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "community_id" INTEGER NOT NULL,
  "agreement" TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS "communityagreement_agreement_community_id_idx" ON "communityagreement_agreement" ("community_id");
