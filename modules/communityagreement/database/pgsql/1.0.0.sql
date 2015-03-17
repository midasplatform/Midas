-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- PostgreSQL database for the communityagreement module, version 1.0.0

SET client_encoding = 'UTF8';
SET default_with_oids = FALSE;

CREATE TABLE IF NOT EXISTS "communityagreement_agreement" (
    "agreement_id" serial PRIMARY KEY,
    "community_id" bigint NOT NULL,
    "agreement" text NOT NULL
);
