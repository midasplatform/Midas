-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- PostgreSQL database for the batchmake module, version 0.1.0

SET client_encoding = 'UTF8';
SET default_with_oids = FALSE;

CREATE TABLE IF NOT EXISTS "batchmake_itemmetric" (
    "itemmetric_id" serial PRIMARY KEY,
    "metric_name" character varying(64) NOT NULL,
    "bms_name" character varying(256) NOT NULL
);

CREATE TABLE IF NOT EXISTS "batchmake_task" (
    "batchmake_task_id" serial PRIMARY KEY,
    "user_id" bigint NOT NULL,
    "work_dir" text
);

CREATE TABLE IF NOT EXISTS "condor_dag" (
    "condor_dag_id" serial PRIMARY KEY,
    "batchmake_task_id" bigint NOT NULL,
    "out_filename" text NOT NULL
);

CREATE TABLE IF NOT EXISTS "condor_job" (
    "condor_job_id" serial PRIMARY KEY,
    "condor_dag_id" bigint NOT NULL,
    "jobdefinition_filename" text NOT NULL,
    "output_filename" text NOT NULL,
    "error_filename" text NOT NULL,
    "log_filename" text NOT NULL
);
