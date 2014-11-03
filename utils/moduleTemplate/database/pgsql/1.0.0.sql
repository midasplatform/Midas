-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- PostgreSQL database for the @MN@ module, version 1.0.0

SET client_encoding = 'UTF8';

CREATE TABLE IF NOT EXISTS "@MN@_thing" (
    "thing_id" serial PRIMARY KEY,
    "creation_date" timestamp without time zone NOT NULL DEFAULT now()
);
