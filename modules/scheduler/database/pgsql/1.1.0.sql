DROP TABLE IF EXISTS scheduler_job;
CREATE TABLE scheduler_job (
  job_id serial PRIMARY KEY,
  task character varying(512) NOT NULL,
  run_only_once smallint NOT NULL,
  fire_time timestamp without time zone NOT NULL DEFAULT now(),
  time_last_fired timestamp without time zone,
  time_interval bigint,
  priority smallint,
  status smallint,
  params text,
  creator_id bigint
);

DROP TABLE IF EXISTS scheduler_job_log;
CREATE TABLE scheduler_job_log (
  log_id serial PRIMARY KEY,
  job_id bigint,
  date timestamp without time zone NOT NULL DEFAULT now(),
  log text
);
