-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- SQLite database for the packages module, version 1.0.0

CREATE TABLE IF NOT EXISTS "packages_application" (
  "application_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "project_id" INTEGER NOT NULL,
  "name" TEXT NOT NULL DEFAULT '',
  "description" TEXT NOT NULL DEFAULT ''
);

CREATE INDEX IF NOT EXISTS "packages_application_project_id_idx" ON "packages_application" ("project_id");

CREATE TABLE IF NOT EXISTS "packages_extension" (
  "extension_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "item_id" INTEGER NOT NULL,
  "application_id" INTEGER NOT NULL,
  "os" TEXT NOT NULL,
  "arch" TEXT NOT NULL,
  "repository_url" TEXT NOT NULL,
  "revision" TEXT NOT NULL,
  "submissiontype" TEXT NOT NULL,
  "packagetype" TEXT NOT NULL,
  "application_revision" TEXT NOT NULL,
  "release" TEXT NOT NULL,
  "icon_url" TEXT NOT NULL,
  "productname" TEXT NOT NULL,
  "codebase" TEXT NOT NULL,
  "development_status" TEXT NOT NULL,
  "category" TEXT NOT NULL DEFAULT '',
  "description" TEXT NOT NULL,
  "enabled" INTEGER NOT NULL DEFAULT 1,
  "homepage" TEXT NOT NULL,
  "repository_type" TEXT NOT NULL DEFAULT '',
  "screenshots" TEXT NOT NULL,
  "contributors" TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS "packages_extension_application_id_idx" ON "packages_extension" ("application_id");
CREATE INDEX IF NOT EXISTS "packages_extension_release_idx" ON "packages_extension" ("release");
CREATE INDEX IF NOT EXISTS "packages_extension_category_idx" ON "packages_extension" ("category");

CREATE TABLE IF NOT EXISTS "packages_extensioncompatibility" (
  "extension_id" INTEGER NOT NULL,
  "core_revision" TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS "packages_extensiondependency" (
  "extension_name" TEXT NOT NULL,
  "extension_dependency" TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS "packages_package" (
  "package_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "item_id" INTEGER NOT NULL,
  "application_id" INTEGER NOT NULL,
  "os" TEXT NOT NULL,
  "arch" TEXT NOT NULL,
  "revision" TEXT NOT NULL,
  "submissiontype" TEXT NOT NULL,
  "packagetype" TEXT NOT NULL,
  "productname" TEXT NOT NULL DEFAULT '',
  "codebase" TEXT NOT NULL DEFAULT '',
  "checkoutdate" TEXT,
  "release" TEXT NOT NULL DEFAULT ''
);

CREATE INDEX IF NOT EXISTS "packages_package_application_id_idx" ON "packages_package" ("application_id");
CREATE INDEX IF NOT EXISTS "packages_package_release_idx" ON "packages_package" ("release");

CREATE TABLE IF NOT EXISTS "packages_project" (
  "project_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "community_id" INTEGER NOT NULL,
  "enabled" INTEGER NOT NULL DEFAULT 1
);
