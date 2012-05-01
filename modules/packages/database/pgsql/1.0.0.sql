CREATE TABLE packages_extension (
  extension_id serial PRIMARY KEY,
  item_id bigint NOT NULL,
  application_id bigint NOT NULL,
  os character varying(255) NOT NULL,
  arch character varying(255) NOT NULL,
  repository_url character varying(255) NOT NULL,
  revision character varying(255) NOT NULL,
  submissiontype character varying(255) NOT NULL,
  packagetype character varying(255) NOT NULL,
  application_revision character varying(255) NOT NULL,
  release character varying(255) NOT NULL,
  icon_url text NOT NULL,
  productname character varying(255) NOT NULL,
  codebase character varying(255) NOT NULL,
  development_status text NOT NULL,
  category character varying(255) NOT NULL DEFAULT '',
  description text NOT NULL,
  enabled int NOT NULL DEFAULT '1',
  homepage text NOT NULL,
  repository_type character varying(10) NOT NULL DEFAULT '',
  screenshots text NOT NULL,
  contributors text NOT NULL
);
CREATE INDEX packages_extension_release ON packages_extension (release);
CREATE INDEX packages_extension_category ON packages_extension (category);
CREATE INDEX packages_extension_application_id ON packages_extension (application_id);


CREATE TABLE packages_extensioncompatibility (
  extension_id bigint NOT NULL,
  core_revision character varying(255) NOT NULL
);


CREATE TABLE packages_extensiondependency (
  extension_name character varying(255) NOT NULL,
  extension_dependency character varying(255) NOT NULL
);


CREATE TABLE packages_package (
  package_id serial PRIMARY KEY,
  item_id bigint NOT NULL,
  application_id bigint NOT NULL,
  os character varying(256) NOT NULL,
  arch character varying(256) NOT NULL,
  revision character varying(256) NOT NULL,
  submissiontype character varying(256) NOT NULL,
  packagetype character varying(256) NOT NULL,
  productname character varying(255) NOT NULL DEFAULT '',
  codebase character varying(255) NOT NULL DEFAULT '',
  checkoutdate timestamp NULL DEFAULT NULL,
  release character varying(255) NOT NULL DEFAULT ''
);
CREATE INDEX packages_package_release ON packages_package (release);
CREATE INDEX packages_package_application_id ON packages_package (application_id);


CREATE TABLE packages_project (
  project_id serial PRIMARY KEY,
  community_id bigint NOT NULL
);


CREATE TABLE packages_application (
  application_id serial PRIMARY KEY,
  project_id bigint NOT NULL,
  name character varying(255) NOT NULL DEFAULT '',
  description text NOT NULL DEFAULT ''
);
CREATE INDEX packages_application_project_id ON packages_application (project_id);
