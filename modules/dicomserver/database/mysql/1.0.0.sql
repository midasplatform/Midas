-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- MySQL database for the dicomserver module, version 1.0.0

CREATE TABLE IF NOT EXISTS `dicomserver_registration` (
    `registration_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `item_id` bigint(20) NOT NULL,
    PRIMARY KEY (`registration_id`)
) DEFAULT CHARSET=utf8;
