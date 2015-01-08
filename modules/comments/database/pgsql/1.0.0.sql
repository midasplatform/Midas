-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- PostgreSQL database for the comments module, version 1.0.0

SET client_encoding = 'UTF8';
SET default_with_oids = FALSE;

CREATE TABLE IF NOT EXISTS "comments_item" (
    "comment_id" serial PRIMARY KEY,
    "item_id" bigint NOT NULL,
    "user_id" bigint NOT NULL,
    "comment" text NOT NULL,
    "date" timestamp without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX "comments_item_item_id" ON "comments_item" ("item_id");
CREATE INDEX "comments_item_user_id" ON "comments_item" ("user_id");
