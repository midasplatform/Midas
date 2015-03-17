-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- PostgreSQL database for the packages module, version 1.0.0

SET client_encoding = 'UTF8';
SET default_with_oids = FALSE;

CREATE TABLE IF NOT EXISTS "packages_application" (
    "application_id" serial PRIMARY KEY,
    "project_id" bigint NOT NULL,
    "name" character varying(255) NOT NULL DEFAULT ''::character varying,
    "description" text NOT NULL
);

CREATE INDEX "packages_application_project_id" ON "packages_application" ("project_id");

CREATE TABLE IF NOT EXISTS "packages_extension" (
    "extension_id" serial PRIMARY KEY,
    "item_id" bigint NOT NULL,
    "application_id" bigint NOT NULL,
    "os" character varying(255) NOT NULL,
    "arch" character varying(255) NOT NULL,
    "repository_url" character varying(255) NOT NULL,
    "revision" character varying(255) NOT NULL,
    "submissiontype" character varying(255) NOT NULL,
    "packagetype" character varying(255) NOT NULL,
    "application_revision" character varying(255) NOT NULL,
    "release" character varying(255) NOT NULL,
    "icon_url" text NOT NULL,
    "productname" character varying(255) NOT NULL,
    "codebase" character varying(255) NOT NULL,
    "development_status" text NOT NULL,
    "category" character varying(255) NOT NULL DEFAULT ''::character varying,
    "description" text NOT NULL,
    "enabled" smallint NOT NULL DEFAULT 1::smallint,
    "homepage" text NOT NULL,
    "repository_type" character varying(10) NOT NULL DEFAULT ''::character varying,
    "screenshots" text NOT NULL,
    "contributors" text NOT NULL
);

CREATE INDEX "packages_extension_application_id" ON "packages_extension" ("application_id");
CREATE INDEX "packages_extension_release" ON "packages_extension" ("release");
CREATE INDEX "packages_extension_category" ON "packages_extension" ("category");

CREATE TABLE IF NOT EXISTS "packages_extensioncompatibility" (
    "extension_id" bigint NOT NULL,
    "core_revision" character varying(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS "packages_extensiondependency" (
    "extension_name" character varying(255) NOT NULL,
    "extension_dependency" character varying(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS "packages_package" (
    "package_id" serial PRIMARY KEY,
    "item_id" bigint NOT NULL,
    "application_id" bigint NOT NULL,
    "os" character varying(256) NOT NULL,
    "arch" character varying(256) NOT NULL,
    "revision" character varying(256) NOT NULL,
    "submissiontype" character varying(256) NOT NULL,
    "packagetype" character varying(256) NOT NULL,
    "productname" character varying(255) NOT NULL DEFAULT ''::character varying,
    "codebase" character varying(255) NOT NULL DEFAULT ''::character varying,
    "checkoutdate" timestamp without time zone,
    "release" character varying(255) NOT NULL DEFAULT ''::character varying
);

CREATE INDEX "packages_package_application_id" ON "packages_package" ("application_id");
CREATE INDEX "packages_package_release" ON "packages_package" ("release");

CREATE TABLE IF NOT EXISTS "packages_project" (
    "project_id" serial PRIMARY KEY,
    "community_id" bigint NOT NULL,
    "enabled" smallint NOT NULL DEFAULT 1::smallint
);
