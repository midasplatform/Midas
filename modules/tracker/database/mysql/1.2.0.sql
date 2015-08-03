-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- MySQL database for the tracker module, version 1.2.0

CREATE TABLE IF NOT EXISTS `tracker_producer` (
    `producer_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `community_id` bigint(20) NOT NULL,
    `repository` varchar(255) NOT NULL,
    `executable_name` varchar(255) NOT NULL,
    `display_name` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `revision_url` text NOT NULL,
    PRIMARY KEY (`producer_id`),
    KEY (`community_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tracker_scalar` (
    `scalar_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `trend_id` bigint(20) NOT NULL,
    `value` double,
    `producer_revision` varchar(255),
    `submit_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `user_id` bigint(20) NOT NULL DEFAULT '-1',
    `submission_id` bigint(20) NOT NULL DEFAULT '-1',
    `official` tinyint(4) NOT NULL DEFAULT '1',
    `build_results_url` text NOT NULL,
    `branch` varchar(255) NOT NULL DEFAULT '',
    `params` text,
    `extra_urls` text,
    PRIMARY KEY (`scalar_id`),
    KEY (`trend_id`),
    KEY (`submit_time`),
    KEY (`user_id`),
    KEY (`submission_id`),
    KEY (`branch`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tracker_submission` (
    `submission_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `producer_id` bigint(20) NOT NULL,
    `name` varchar(255) NOT NULL DEFAULT '',
    `uuid` varchar(255) NOT NULL DEFAULT '',
    `submit_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`submission_id`),
    UNIQUE KEY (`uuid`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tracker_scalar2item` (
    `scalar_id` bigint(20) NOT NULL,
    `item_id` bigint(20) NOT NULL,
    `label` varchar(255) NOT NULL,
    KEY (`scalar_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tracker_threshold_notification` (
    `threshold_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `trend_id` bigint(20) NOT NULL,
    `value` double,
    `comparison` varchar(2),
    `action` varchar(80) NOT NULL,
    `recipient_id` bigint(20) NOT NULL,
    PRIMARY KEY (`threshold_id`),
    KEY (`trend_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tracker_trend` (
    `trend_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `producer_id` bigint(20) NOT NULL,
    `metric_name` varchar(255) NOT NULL,
    `display_name` varchar(255) NOT NULL,
    `unit` varchar(255) NOT NULL,
    `config_item_id` bigint(20),
    `test_dataset_id` bigint(20),
    `truth_dataset_id` bigint(20),
    PRIMARY KEY (`trend_id`),
    KEY (`producer_id`)
) DEFAULT CHARSET=utf8;
