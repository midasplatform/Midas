-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- PostgreSQL database for the validation module, version 1.0.0

SET client_encoding = 'UTF8';
SET default_with_oids = FALSE;

CREATE TABLE IF NOT EXISTS "validation_dashboard" (
    "dashboard_id" serial PRIMARY KEY,
    "owner_id" bigint NOT NULL,
    "name" character varying(255) NOT NULL,
    "description" text NOT NULL,
    "truthfolder_id" bigint DEFAULT NULL,
    "testingfolder_id" bigint DEFAULT NULL,
    "trainingfolder_id" bigint DEFAULT NULL,
    "min" double precision DEFAULT NULL,
    "max" double precision DEFAULT NULL,
    "metric_id" bigint DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS "validation_dashboard2folder" (
    "dashboard2folder_id" serial PRIMARY KEY,
    "dashboard_id" bigint NOT NULL,
    "folder_id" bigint NOT NULL
);

CREATE TABLE IF NOT EXISTS "validation_dashboard2scalarresult" (
    "dashboard2scalarresult_id" serial PRIMARY KEY,
    "dashboard_id" bigint NOT NULL,
    "scalarresult_id" bigint NOT NULL
);

CREATE TABLE IF NOT EXISTS "validation_scalarresult" (
    "scalarresult_id" serial PRIMARY KEY,
    "folder_id" bigint NOT NULL,
    "item_id" bigint NOT NULL,
    value double precision DEFAULT NULL
);
