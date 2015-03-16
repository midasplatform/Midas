-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- PostgreSQL database for the example module, version 1.0.0

SET client_encoding = 'UTF8';
SET default_with_oids = FALSE;

CREATE TABLE IF NOT EXISTS "example_wallet" (
    "example_wallet_id" serial PRIMARY KEY,
    "user_id" bigint NOT NULL,
    "dollars" bigint NOT NULL
);
