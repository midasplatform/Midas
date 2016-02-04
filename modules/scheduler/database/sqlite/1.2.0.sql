-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- SQLite database for the scheduler module, version 1.2.0

CREATE TABLE IF NOT EXISTS "scheduler_job" (
  "job_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "task" TEXT NOT NULL,
  "run_only_once" INTEGER NOT NULL,
  "fire_time" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "time_last_fired" TEXT,
  "time_interval" INTEGER,
  "priority" INTEGER,
  "status" INTEGER,
  "params" TEXT,
  "creator_id" INTEGER
);
