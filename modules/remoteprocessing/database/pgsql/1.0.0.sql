
CREATE TABLE remoteprocessing_job
(
  job_id serial PRIMARY KEY,
  os character varying(512) NOT NULL,
  condition character varying(512) NOT NULL DEFAULT '',
  script text,
  params text,
  status smallint NOT NULL DEFAULT 0,
  expiration_date timestamp without time zone,
  creation_date timestamp without time zone,
  start_date timestamp without time zone
);


CREATE TABLE remoteprocessing_job2item
(
  job_id bigint NOT NULL,
  item_id bigint NOT NULL,
  type smallint NOT NULL DEFAULT 0
);
