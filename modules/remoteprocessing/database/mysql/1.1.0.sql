-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- MySQL database for the remoteprocessing module, version 1.1.0

CREATE TABLE IF NOT EXISTS `remoteprocessing_job` (
    `job_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `os` varchar(512) NOT NULL,
    `condition` varchar(512) NOT NULL DEFAULT '',
    `script` text,
    `params` text,
    `status` tinyint(4) NOT NULL DEFAULT '0',
    `expiration_date` timestamp,
    `creation_date` timestamp,
    `start_date` timestamp,
    `creator_id` bigint(20),
    `name` varchar(512),
    PRIMARY KEY (`job_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `remoteprocessing_job2item` (
    `job_id` bigint(20) NOT NULL,
    `item_id` bigint(20) NOT NULL,
    `type` tinyint(4) NOT NULL DEFAULT '0'
) DEFAULT CHARSET=utf8;
