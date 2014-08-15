CREATE TABLE IF NOT EXISTS `scheduler_job` (
  `job_id` bigint(20) AUTO_INCREMENT,
  `task` varchar(512) NOT NULL,
  `run_only_once` tinyint(4) NOT NULL,
  `fire_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `time_last_fired` timestamp,
  `time_interval` bigint(20),
  `priority` tinyint(4),
  `status`  tinyint(4),
  `params` text,
  `creator_id` bigint(20),
  PRIMARY KEY (`job_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `scheduler_job_log` (
  `log_id` bigint(20) AUTO_INCREMENT,
  `job_id` bigint(20),
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `log` text,
  PRIMARY KEY (`log_id`)
) DEFAULT CHARSET=utf8;
