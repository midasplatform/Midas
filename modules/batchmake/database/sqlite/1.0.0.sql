-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- SQLite database for the batchmake module, version 1.0.0

CREATE TABLE IF NOT EXISTS "batchmake_itemmetric" (
    "itemmetric_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "metric_name" TEXT NOT NULL,
    "bms_name" TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS "batchmake_task" (
    "batchmake_task_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "user_id" INTEGER NOT NULL,
    "work_dir" TEXT
);

CREATE TABLE IF NOT EXISTS "condor_dag" (
    "condor_dag_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "batchmake_task_id" INTEGER NOT NULL,
    "out_filename" TEXT NOT NULL,
    "dag_filename" TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS "condor_job" (
    "condor_job_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "condor_dag_id" INTEGER NOT NULL,
    "jobdefinition_filename" TEXT NOT NULL,
    "output_filename" TEXT NOT NULL,
    "error_filename" TEXT NOT NULL,
    "log_filename" TEXT NOT NULL,
    "post_filename" TEXT NOT NULL
);
