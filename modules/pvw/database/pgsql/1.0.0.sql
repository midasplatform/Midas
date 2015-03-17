-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- PostgreSQL database for the pvw module, version 1.0.0

SET client_encoding = 'UTF8';
SET default_with_oids = FALSE;

CREATE TABLE IF NOT EXISTS "pvw_instance" (
    "instance_id" serial PRIMARY KEY,
    "item_id" bigint NOT NULL,
    "port" integer NOT NULL,
    "pid" integer NOT NULL,
    "sid" character varying(127) NOT NULL,
    "secret" character varying(64) NOT NULL,
    "creation_date" timestamp without time zone NOT NULL
);
