-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- MySQL database for the sizequota module, version 1.0.0

CREATE TABLE IF NOT EXISTS `sizequota_folderquota` (
    `folderquota_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `folder_id` bigint(20) NOT NULL,
    `quota` varchar(50) NOT NULL,
    PRIMARY KEY (`folderquota_id`),
    KEY (`folder_id`)
) DEFAULT CHARSET=utf8;
