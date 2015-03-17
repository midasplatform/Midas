-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- PostgreSQL database for the mfa module, version 1.0.0

SET client_encoding = 'UTF8';
SET default_with_oids = FALSE;

CREATE TABLE IF NOT EXISTS "mfa_otpdevice" (
    "otpdevice_id" serial PRIMARY KEY,
    "user_id" bigint NOT NULL,
    "secret" character varying(256) NOT NULL,
    "algorithm" character varying(256) NOT NULL,
    "counter" character varying(256) NOT NULL DEFAULT ''::character varying,
    "length" integer NOT NULL
);

CREATE TABLE IF NOT EXISTS "mfa_apitoken" (
    "apitoken_id" serial PRIMARY KEY,
    "user_id" bigint NOT NULL,
    "token_id" bigint NOT NULL,
    "creation_date" timestamp without time zone NOT NULL
);
