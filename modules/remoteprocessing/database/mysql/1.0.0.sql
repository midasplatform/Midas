
CREATE TABLE IF NOT EXISTS `remoteprocessing_job` (
  `job_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `os` varchar(512) NOT NULL,
  `condition` varchar(512) NOT NULL,
  `script`  text,
  `params`  text,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `expiration_date` timestamp NULL DEFAULT NULL ,
  `creation_date` timestamp NULL DEFAULT NULL ,
  `start_date` timestamp NULL DEFAULT NULL ,
  PRIMARY KEY (`job_id`)
);


CREATE TABLE IF NOT EXISTS `remoteprocessing_job2item` (
  `job_id` bigint(20) NOT NULL,
  `item_id` bigint(20) NOT NULL,
  `type` tinyint(4) NOT NULL DEFAULT 0
);

