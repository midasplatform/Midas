-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- MySQL database for the @MN@ module, version 1.0.0

CREATE TABLE IF NOT EXISTS `@MN@_thing` (
    `thing_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `creation_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`thing_id`)
) DEFAULT CHARSET=utf8;
