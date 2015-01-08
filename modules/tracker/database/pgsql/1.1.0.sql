-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- PostgreSQL database for the tracker module, version 1.1.0

SET client_encoding = 'UTF8';
SET default_with_oids = FALSE;

CREATE TABLE IF NOT EXISTS "tracker_producer" (
    "producer_id" serial PRIMARY KEY,
    "community_id" bigint NOT NULL,
    "repository" character varying(255) NOT NULL,
    "executable_name" character varying(255) NOT NULL,
    "display_name" character varying(255) NOT NULL,
    "description" text NOT NULL,
    "revision_url" text NOT NULL
);

CREATE INDEX "tracker_producer_community_id" ON "tracker_producer" ("community_id");

CREATE TABLE IF NOT EXISTS "tracker_scalar" (
    "scalar_id" serial PRIMARY KEY,
    "trend_id" bigint NOT NULL,
    "value" double precision,
    "producer_revision" character varying(255),
    "submit_time" timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "user_id" bigint NOT NULL DEFAULT -1::bigint,
    "official" smallint NOT NULL DEFAULT 1::smallint,
    "build_results_url" text NOT NULL,
    "branch" character varying(255) NOT NULL DEFAULT ''::character varying,
    "params" text,
    "extra_urls" text
);

CREATE INDEX "tracker_scalar_trend_id" ON "tracker_scalar" ("trend_id");
CREATE INDEX "tracker_scalar_submit_time" ON "tracker_scalar" ("submit_time");
CREATE INDEX "tracker_scalar_idx_branch" ON "tracker_scalar" ("branch");
CREATE INDEX "tracker_scalar_idx_user_id" ON "tracker_scalar" ("user_id");

CREATE TABLE IF NOT EXISTS "tracker_scalar2item" (
    "id" serial PRIMARY KEY,
    "scalar_id" bigint NOT NULL,
    "item_id" bigint NOT NULL,
    "label" character varying(255) NOT NULL
);

CREATE INDEX "tracker_scalar2item_scalar_id" ON "tracker_scalar2item" ("scalar_id");

CREATE TABLE IF NOT EXISTS "tracker_threshold_notification" (
    "threshold_id" serial PRIMARY KEY,
    "trend_id" bigint NOT NULL,
    "value" double precision,
    "comparison" character varying(2),
    "action" character varying(80) NOT NULL,
    "recipient_id" bigint NOT NULL
);

CREATE INDEX "tracker_threshold_notification_trend_id" ON "tracker_threshold_notification" ("trend_id");

CREATE TABLE IF NOT EXISTS "tracker_trend" (
    "trend_id" serial PRIMARY KEY,
    "producer_id" bigint NOT NULL,
    "metric_name" character varying(255) NOT NULL,
    "display_name" character varying(255) NOT NULL,
    "unit" character varying(255) NOT NULL,
    "config_item_id" bigint,
    "test_dataset_id" bigint,
    "truth_dataset_id" bigint
);

CREATE INDEX "tracker_trend_producer_id" ON "tracker_trend" ("producer_id");
