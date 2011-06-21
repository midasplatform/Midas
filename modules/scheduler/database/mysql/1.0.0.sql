
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
