-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- PostgreSQL database for the ldap module, version 1.0.0

SET client_encoding = 'UTF8';
SET default_with_oids = FALSE;

CREATE TABLE IF NOT EXISTS "ldap_user" (
    "ldap_user_id" serial PRIMARY KEY,
    "user_id" bigint NOT NULL,
    "login" character varying(255) NOT NULL
);

CREATE INDEX "ldap_user_login_idx" ON "ldap_user" ("login");
