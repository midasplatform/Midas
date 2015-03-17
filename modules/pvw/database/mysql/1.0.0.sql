-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- MySQL database for the pvw module, version 1.0.0

CREATE TABLE IF NOT EXISTS `pvw_instance` (
    `instance_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `item_id` bigint(20) NOT NULL,
    `port` integer unsigned NOT NULL,
    `pid` integer unsigned NOT NULL,
    `sid` varchar(127) NOT NULL,
    `secret` varchar(64) NOT NULL,
    `creation_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`instance_id`)
) DEFAULT CHARSET=utf8;
