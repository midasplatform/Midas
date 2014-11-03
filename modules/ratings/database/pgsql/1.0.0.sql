-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- PostgreSQL database for the ratings module, version 1.0.0

SET client_encoding = 'UTF8';
SET default_with_oids = FALSE;

CREATE TABLE IF NOT EXISTS "ratings_item" (
    "rating_id" serial PRIMARY KEY,
    "item_id" bigint NOT NULL,
    "user_id" bigint NOT NULL,
    "rating" smallint NOT NULL
);

CREATE INDEX "ratings_item_item_id" ON "ratings_item" ("item_id");
CREATE INDEX "ratings_item_user_id" ON "ratings_item" ("user_id");
