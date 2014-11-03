-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- PostgreSQL database for the sizequota module, version 1.0.0

SET client_encoding = 'UTF8';
SET default_with_oids = FALSE;

CREATE TABLE IF NOT EXISTS "sizequota_folderquota" (
    "folderquota_id" serial PRIMARY KEY,
    "folder_id" bigint NOT NULL,
    "quota" character varying(50) NOT NULL
);

CREATE INDEX "sizequota_folderquota_folder_id" ON "sizequota_folderquota" ("folder_id");
