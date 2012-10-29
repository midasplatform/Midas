CREATE TABLE IF NOT EXISTS `tracker_producer` (
  `producer_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `community_id` bigint(20) NOT NULL,
  `repository` varchar(255) NOT NULL,
  `executable_name` varchar(255) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`producer_id`),
  KEY `community_id` (`community_id`)
);

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
  KEY `producer_id` (`producer_id`)
);

CREATE TABLE IF NOT EXISTS `tracker_scalar` (
  `scalar_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `trend_id` bigint(20) NOT NULL,
  `value` double precision,
  `producer_revision` varchar(255),
  `submit_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`scalar_id`),
  KEY `trend_id` (`trend_id`),
  KEY `submit_time` (`submit_time`)
);

CREATE TABLE IF NOT EXISTS `tracker_scalar2item` (
  `scalar_id` bigint(20) NOT NULL,
  `item_id` bigint(20) NOT NULL,
  `label` varchar(255) NOT NULL,
  KEY `scalar_id` (`scalar_id`)
);

CREATE TABLE IF NOT EXISTS `tracker_threshold_notification` (
  `threshold_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `trend_id` bigint(20) NOT NULL,
  `value` double precision,
  `comparison` varchar(2),
  `action` varchar(80) NOT NULL,
  `recipient_id` bigint(20) NOT NULL,
  PRIMARY KEY (`threshold_id`),
  KEY `trend_id` (`trend_id`)
);
