-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- PostgreSQL database for the scheduler module, version 1.1.0

SET client_encoding = 'UTF8';
SET default_with_oids = FALSE;

CREATE TABLE IF NOT EXISTS "scheduler_job" (
    "job_id" serial PRIMARY KEY,
    "task" character varying(512) NOT NULL,
    "run_only_once" smallint NOT NULL,
    "fire_time" timestamp without time zone NOT NULL DEFAULT now(),
    "time_last_fired" timestamp without time zone,
    "time_interval" bigint,
    "priority" smallint,
    "status" smallint,
    "params" text,
    "creator_id" bigint
);

CREATE TABLE IF NOT EXISTS "scheduler_job_log" (
    "log_id" serial PRIMARY KEY,
    "job_id" bigint,
    "date" timestamp without time zone NOT NULL DEFAULT now(),
    "log" text
);
