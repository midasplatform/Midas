DROP TABLE IF EXISTS scheduler_execution;
CREATE TABLE scheduler_execution (
  workflow_id bigint NOT NULL,
  execution_id serial,
  execution_parent bigint DEFAULT NULL,
  execution_started integer NOT NULL,
  execution_suspended integer DEFAULT NULL,
  execution_variables bytea,
  execution_waiting_for bytea,
  execution_threads bytea,
  execution_next_thread_id bigint NOT NULL,
  PRIMARY KEY (execution_id, workflow_id)
);
CREATE INDEX scheduler_execution_execution_parent ON scheduler_execution (execution_parent);


DROP TABLE IF EXISTS scheduler_execution_state;
CREATE TABLE scheduler_execution_state (
  execution_id bigint NOT NULL,
  node_id bigint NOT NULL,
  node_state bytea,
  node_activated_from bytea,
  node_thread_id bigint NOT NULL,
  PRIMARY KEY (execution_id, node_id)
);


DROP TABLE IF EXISTS scheduler_job;
CREATE TABLE scheduler_job (
  job_id serial PRIMARY KEY,
  task character varying(512) NOT NULL,
  run_only_once smallint NOT NULL,
  fire_time timestamp without time zone DEFAULT now(),
  time_last_fired timestamp without time zone,
  time_interval bigint DEFAULT NULL,
  priority smallint DEFAULT NULL,
  status smallint DEFAULT NULL,
  params text,
  creator_id bigint DEFAULT NULL
);


DROP TABLE IF EXISTS scheduler_job_log;
CREATE TABLE scheduler_job_log (
  log_id serial PRIMARY KEY,
  job_id bigint DEFAULT NULL,
  date timestamp without time zone NOT NULL DEFAULT now(),
  log text
);


DROP TABLE IF EXISTS scheduler_node;
CREATE TABLE scheduler_node (
  node_id serial PRIMARY KEY,
  workflow_id bigint NOT NULL,
  node_class character varying(255) NOT NULL,
  node_configuration bytea
);
CREATE INDEX scheduler_node_workflow_id ON scheduler_node (workflow_id);


DROP TABLE IF EXISTS scheduler_node_connection;
CREATE TABLE scheduler_node_connection (
  node_connection_id serial PRIMARY KEY,
  incoming_node_id bigint NOT NULL,
  outgoing_node_id bigint NOT NULL
);


DROP TABLE IF EXISTS scheduler_variable_handler;
CREATE TABLE scheduler_variable_handler (
  workflow_id bigint NOT NULL,
  variable character varying(255) NOT NULL,
  class character varying(255) NOT NULL,
  PRIMARY KEY (workflow_id, class)
);


DROP TABLE IF EXISTS scheduler_workflow;
CREATE TABLE scheduler_workflow (
  workflow_id serial PRIMARY KEY,
  workflow_name character varying(255) NOT NULL,
  workflow_version bigint NOT NULL DEFAULT '1',
  workflow_created integer NOT NULL,
  UNIQUE (workflow_name, workflow_version)
);
