-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- SQLite database for the scheduler module, version 1.1.0

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

CREATE TABLE IF NOT EXISTS "scheduler_job_log" (
  "log_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "job_id" INTEGER,
  "date" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "log" TEXT
);
