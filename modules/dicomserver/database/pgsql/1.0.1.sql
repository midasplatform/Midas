-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- PostgreSQL database for the dicomserver module, version 1.0.1

SET client_encoding = 'UTF8';
SET default_with_oids = FALSE;

CREATE TABLE IF NOT EXISTS "dicomserver_registration" (
    "registration_id" serial PRIMARY KEY,
    "item_id" bigint NOT NULL
);
