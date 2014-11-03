-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- PostgreSQL database for the googleauth module, version 1.0.0

SET client_encoding = 'UTF8';
SET default_with_oids = FALSE;

CREATE TABLE "googleauth_user" (
    "googleauth_user_id" serial PRIMARY KEY,
    "google_person_id" character varying(255) NOT NULL,
    "user_id" bigint NOT NULL
);

CREATE INDEX "googleauth_user_user_id_idx" ON "googleauth_user" ("user_id");
CREATE INDEX "googleauth_user_gperson_id_idx" ON "googleauth_user" ("google_person_id");
