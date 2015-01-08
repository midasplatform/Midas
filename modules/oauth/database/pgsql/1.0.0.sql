-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- PostgreSQL database for the oauth module, version 1.0.0

SET client_encoding = 'UTF8';
SET default_with_oids = FALSE;

CREATE TABLE IF NOT EXISTS "oauth_client" (
    "client_id" serial PRIMARY KEY,
    "name" character varying(255) NOT NULL DEFAULT ''::character varying,
    "secret" character varying(64) NOT NULL,
    "owner_id" bigint NOT NULL,
    "creation_date" timestamp without time zone
);

CREATE INDEX "oauth_client_owner_id" ON "oauth_client" ("owner_id");

CREATE TABLE IF NOT EXISTS "oauth_code" (
    "code_id" serial PRIMARY KEY,
    "code" character varying(64) NOT NULL,
    "scopes" character varying(255) NOT NULL,
    "user_id" bigint NOT NULL,
    "client_id" bigint NOT NULL,
    "creation_date" timestamp without time zone,
    "expiration_date" timestamp without time zone
);

CREATE INDEX "oauth_code_code" ON "oauth_code" ("code");

CREATE TABLE IF NOT EXISTS "oauth_token" (
    "token_id" serial PRIMARY KEY,
    "client_id" bigint NOT NULL,
    "user_id" bigint NOT NULL,
    "token" character varying(64) NOT NULL,
    "scopes" character varying(255) NOT NULL,
    "type" smallint NOT NULL,
    "creation_date" timestamp without time zone,
    "expiration_date" timestamp without time zone
);

CREATE INDEX "oauth_token_token" ON "oauth_token" ("token");
CREATE INDEX "oauth_token_user_id" ON "oauth_token" ("user_id");
