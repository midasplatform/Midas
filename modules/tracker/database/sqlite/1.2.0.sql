-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- SQLite database for the tracker module, version 1.2.0

CREATE TABLE IF NOT EXISTS "tracker_producer" (
    "producer_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "community_id" INTEGER NOT NULL,
    "repository" TEXT NOT NULL,
    "executable_name" TEXT NOT NULL,
    "display_name" TEXT NOT NULL,
    "description" TEXT NOT NULL,
    "revision_url" TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS "tracker_producer_community_id_idx" ON "tracker_producer" ("community_id");

CREATE TABLE IF NOT EXISTS "tracker_scalar" (
    "scalar_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "trend_id" INTEGER NOT NULL,
    "value" REAL,
    "producer_revision" TEXT,
    "submit_time" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "user_id" INTEGER NOT NULL DEFAULT -1,
    "submission_id" INTEGER NOT NULL DEFAULT -1,
    "official" INTEGER NOT NULL DEFAULT 1,
    "build_results_url" TEXT NOT NULL,
    "branch" TEXT NOT NULL DEFAULT '',
    "params" TEXT,
    "extra_urls" TEXT
);

CREATE INDEX IF NOT EXISTS "tracker_scalar_trend_id_idx" ON "tracker_scalar" ("trend_id");
CREATE INDEX IF NOT EXISTS "tracker_scalar_submit_time_idx" ON "tracker_scalar" ("submit_time");
CREATE INDEX IF NOT EXISTS "tracker_scalar_branch_idx" ON "tracker_scalar" ("branch");
CREATE INDEX IF NOT EXISTS "tracker_scalar_user_id_idx" ON "tracker_scalar" ("user_id");


CREATE TABLE IF NOT EXISTS "tracker_submission" (
    "submission_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "producer_id" INTEGER NOT NULL,
    "name" TEXT NOT NULL DEFAULT '',
    "uuid" TEXT NOT NULL DEFAULT '',
    "submit_time" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX IF NOT EXISTS "tracker_submission_uuid_idx" ON "tracker_submission" ("uuid");
CREATE INDEX IF NOT EXISTS "tracker_submission_submit_time_idx" ON "tracker_submission" ("submit_time");


CREATE TABLE IF NOT EXISTS "tracker_scalar2item" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "scalar_id" INTEGER NOT NULL,
    "item_id" INTEGER NOT NULL,
    "label" TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS "tracker_scalar2item_scalar_id_idx" ON "tracker_scalar2item" ("scalar_id");

CREATE TABLE IF NOT EXISTS "tracker_threshold_notification" (
    "threshold_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "trend_id" INTEGER NOT NULL,
    "value" REAL,
    "comparison" TEXT,
    "action" TEXT NOT NULL,
    "recipient_id" INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS "tracker_threshold_notification_trend_id_idx" ON "tracker_threshold_notification" ("trend_id");

CREATE TABLE IF NOT EXISTS "tracker_trend" (
    "trend_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "producer_id" INTEGER NOT NULL,
    "metric_name" TEXT NOT NULL,
    "display_name" TEXT NOT NULL,
    "unit" TEXT NOT NULL,
    "config_item_id" INTEGER,
    "test_dataset_id" INTEGER,
    "truth_dataset_id" INTEGER
);

CREATE INDEX IF NOT EXISTS "tracker_trend_producer_id_idx" ON "tracker_trend" ("producer_id");
