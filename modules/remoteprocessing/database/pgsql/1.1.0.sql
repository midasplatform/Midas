-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- PostgreSQL database for the remoteprocessing module, version 1.1.0

SET client_encoding = 'UTF8';
SET default_with_oids = FALSE;

CREATE TABLE IF NOT EXISTS "remoteprocessing_job" (
    "job_id" serial PRIMARY KEY,
    "os" character varying(512) NOT NULL,
    "condition" character varying(512) NOT NULL DEFAULT ''::character varying,
    "script" text,
    "params" text,
    "status" smallint NOT NULL DEFAULT 0::smallint,
    "expiration_date" timestamp without time zone,
    "creation_date" timestamp without time zone,
    "start_date" timestamp without time zone,
    "creator_id" bigint,
    "name" character varying(512)
);

CREATE TABLE IF NOT EXISTS "remoteprocessing_job2item" (
    "id" serial PRIMARY KEY,
    "job_id" bigint NOT NULL,
    "item_id" bigint NOT NULL,
    "type" smallint NOT NULL DEFAULT 0::smallint
);
