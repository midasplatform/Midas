-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- MySQL database for the statistics module, version 1.0.0

CREATE TABLE IF NOT EXISTS `statistics_download` (
    `download_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `item_id` bigint(20) NOT NULL,
    `user_id` bigint(20),
    `ip` varchar(50) NOT NULL,
    `latitude` varchar(50),
    `longitude` varchar(50),
    `date` timestamp,
    PRIMARY KEY (`download_id`)
) DEFAULT CHARSET=utf8;
