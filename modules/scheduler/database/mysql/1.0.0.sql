
CREATE TABLE IF NOT EXISTS `scheduler_job` (
  `job_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `task` varchar(512) NOT NULL,
  `run_only_once`  tinyint(4) NOT NULL,
  `fire_time`  timestamp,
  `time_last_fired`  timestamp,
  `time_interval`  bigint(20),
  `priority`  tinyint(4),
  `status`  tinyint(4),
  `params`  text,
  PRIMARY KEY (`job_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=10 ;


DROP TABLE IF EXISTS scheduler_workflow;
CREATE TABLE scheduler_workflow (
  workflow_id      INTEGER      UNSIGNED NOT NULL AUTO_INCREMENT,
  workflow_name    VARCHAR(255)          NOT NULL,
  workflow_version INTEGER      UNSIGNED NOT NULL DEFAULT 1,
  workflow_created INTEGER               NOT NULL,

  PRIMARY KEY              (workflow_id),
  UNIQUE  KEY name_version (workflow_name, workflow_version)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS scheduler_node;
CREATE TABLE scheduler_node (
  workflow_id        INTEGER      UNSIGNED NOT NULL REFERENCES workflow.workflow_id,
  node_id            INTEGER      UNSIGNED NOT NULL AUTO_INCREMENT,
  node_class         VARCHAR(255)          NOT NULL,
  node_configuration BLOB                      NULL,

  PRIMARY KEY             (node_id),
          KEY workflow_id (workflow_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS scheduler_node_connection;
CREATE TABLE scheduler_node_connection (
  node_connection_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  incoming_node_id   INTEGER UNSIGNED NOT NULL,
  outgoing_node_id   INTEGER UNSIGNED NOT NULL,

  PRIMARY KEY (node_connection_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS scheduler_variable_handler;
CREATE TABLE scheduler_variable_handler (
  workflow_id INTEGER      UNSIGNED NOT NULL REFERENCES workflow.workflow_id,
  variable    VARCHAR(255)          NOT NULL,
  class       VARCHAR(255)          NOT NULL,

  PRIMARY KEY (workflow_id, class)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS scheduler_execution;
CREATE TABLE scheduler_execution (
  workflow_id              INTEGER UNSIGNED NOT NULL REFERENCES workflow.workflow_id,
  execution_id             INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  execution_parent         INTEGER UNSIGNED     NULL REFERENCES execution.execution_id,
  execution_started        INTEGER          NOT NULL,
  execution_suspended      INTEGER              NULL,
  execution_variables      BLOB                 NULL,
  execution_waiting_for    BLOB                 NULL,
  execution_threads        BLOB                 NULL,
  execution_next_thread_id INTEGER UNSIGNED NOT NULL,

  PRIMARY KEY                  (execution_id, workflow_id),
          KEY execution_parent (execution_parent)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS scheduler_execution_state;
CREATE TABLE scheduler_execution_state (
  execution_id        INTEGER UNSIGNED NOT NULL REFERENCES execution.execution_id,
  node_id             INTEGER UNSIGNED NOT NULL REFERENCES node.node_id,
  node_state          BLOB                 NULL,
  node_activated_from BLOB                 NULL,
  node_thread_id      INTEGER UNSIGNED NOT NULL,

  PRIMARY KEY (execution_id, node_id)
) ENGINE=InnoDB;