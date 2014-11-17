-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- MySQL database for the statistics module, version 1.1.0

CREATE TABLE IF NOT EXISTS `statistics_download` (
    `download_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `item_id` bigint(20) NOT NULL,
    `user_id` bigint(20),
    `date` timestamp,
    `user_agent` text,
    `ip_location_id` bigint(20),
    PRIMARY KEY (`download_id`),
    KEY (`item_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `statistics_ip_location` (
    `ip_location_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `ip` varchar(50),
    `latitude` varchar(50),
    `longitude` varchar(50),
    PRIMARY KEY (`ip_location_id`),
    UNIQUE KEY (`ip`),
    KEY (`latitude`)
) DEFAULT CHARSET=utf8;
