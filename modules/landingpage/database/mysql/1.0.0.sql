-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- MySQL database for the landingpage module, version 1.0.0

CREATE TABLE IF NOT EXISTS `landingpage_text` (
    `landingpage_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `text` text,
    PRIMARY KEY (`landingpage_id`)
) DEFAULT CHARSET=utf8;
