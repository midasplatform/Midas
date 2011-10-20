
CREATE TABLE IF NOT EXISTS `remoteprocessing_job` (
  `job_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `os` varchar(512) NOT NULL,
  `condition` varchar(512) NOT NULL,
  `script`  text,
  `params`  text,
  PRIMARY KEY (`job_id`)
);
