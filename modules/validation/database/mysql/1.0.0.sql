-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- MySQL database for the validation module, version 1.0.0

CREATE TABLE IF NOT EXISTS `validation_dashboard` (
    `dashboard_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `owner_id` bigint(20) NOT NULL DEFAULT '0',
    `name` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `truthfolder_id` bigint(20),
    `testingfolder_id` bigint(20),
    `trainingfolder_id` bigint(20),
    `min` double,
    `max` double,
    `metric_id` bigint(20),
    PRIMARY KEY (`dashboard_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `validation_dashboard2folder` (
    `dashboard2folder_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `dashboard_id` bigint(20) NOT NULL,
    `folder_id` bigint(20) NOT NULL,
    PRIMARY KEY (`dashboard2folder_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `validation_dashboard2scalarresult` (
    `dashboard2scalarresult_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `dashboard_id` bigint(20) NOT NULL,
    `scalarresult_id` bigint(20) NOT NULL,
    PRIMARY KEY (`dashboard2scalarresult_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `validation_scalarresult` (
    `scalarresult_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `folder_id` bigint(20) NOT NULL,
    `item_id` bigint(20) NOT NULL,
    `value` double,
    PRIMARY KEY (`scalarresult_id`)
) DEFAULT CHARSET=utf8;
