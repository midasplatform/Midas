-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- MySQL core database, version 3.0.1

CREATE TABLE IF NOT EXISTS `assetstore` (
    `assetstore_id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `path` varchar(512) NOT NULL,
    `type` tinyint(4) NOT NULL,
    PRIMARY KEY (`assetstore_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `bitstream` (
    `bitstream_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `itemrevision_id` bigint(20) NOT NULL,
    `name` varchar(255) NOT NULL,
    `mimetype` varchar(30) NOT NULL,
    `sizebytes` bigint(20) NOT NULL,
    `checksum` varchar(64) NOT NULL,
    `path` varchar(512) NOT NULL,
    `assetstore_id` int(11) NOT NULL,
    `date` timestamp NOT NULL,
    PRIMARY KEY (`bitstream_id`),
    KEY `itemrevision_id` (`itemrevision_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `community` (
    `community_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `creation` timestamp NULL,
    `privacy` tinyint(4) NOT NULL,
    `folder_id` bigint(20) NOT NULL,
    `publicfolder_id` bigint(20) NOT NULL,
    `privatefolder_id` bigint(20) NOT NULL,
    `admingroup_id` bigint(20) NOT NULL,
    `moderatorgroup_id` bigint(20) NOT NULL,
    `membergroup_id` bigint(20) NOT NULL,
    `view` bigint(20) NOT NULL DEFAULT '0',
    PRIMARY KEY (`community_id`),
    KEY `name` (`name`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `errorlog` (
    `errorlog_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `priority` tinyint(4) NOT NULL,
    `module` varchar(255) NOT NULL,
    `message` text NOT NULL,
    `datetime` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`errorlog_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `feed` (
    `feed_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `date` timestamp NOT NULL,
    `user_id` bigint(20) NOT NULL,
    `type` tinyint(4) NOT NULL,
    `ressource` varchar(255) NOT NULL,
    PRIMARY KEY (`feed_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `feed2community` (
    `feed_id` bigint(20) NOT NULL,
    `community_id` bigint(20) NOT NULL,
    UNIQUE KEY `feed_id` (`feed_id`, `community_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `feedpolicygroup` (
    `feed_id` bigint(20) NOT NULL,
    `group_id` bigint(20) NOT NULL,
    `policy` tinyint(4) NOT NULL,
    `date` timestamp NULL DEFAULT CURRENT_TIMESTAMP(),
    UNIQUE KEY `feed_id` (`feed_id`, `group_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `feedpolicyuser` (
    `feed_id` bigint(20) NOT NULL,
    `user_id` bigint(20) NOT NULL,
    `policy` tinyint(4) NOT NULL,
    `date` timestamp NULL DEFAULT CURRENT_TIMESTAMP(),
    UNIQUE KEY `feed_id` (`feed_id`, `user_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `folder` (
    `folder_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `left_indice` bigint(20) NOT NULL,
    `right_indice` bigint(20) NOT NULL,
    `parent_id` bigint(20) NOT NULL DEFAULT '0',
    `name` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `date` timestamp NOT NULL,
    `view` bigint(20) NOT NULL DEFAULT '0',
    PRIMARY KEY (`folder_id`),
    KEY `parent_id` (`parent_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `folderpolicygroup` (
    `folder_id` bigint(20) NOT NULL,
    `group_id` bigint(20) NOT NULL,
    `policy` tinyint(4) NOT NULL,
    `date` timestamp NULL DEFAULT CURRENT_TIMESTAMP(),
    UNIQUE KEY `folder_id` (`folder_id`, `group_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `folderpolicyuser` (
    `folder_id` bigint(20) NOT NULL,
    `user_id` bigint(20) NOT NULL,
    `policy` tinyint(4) NOT NULL,
    `date` timestamp NULL DEFAULT CURRENT_TIMESTAMP(),
    UNIQUE KEY `folder_id` (`folder_id`, `user_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `group` (
    `group_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `community_id` bigint(20) NOT NULL,
    `name` varchar(255) NOT NULL,
    PRIMARY KEY (`group_id`),
    KEY `community_id` (`community_id`)
) DEFAULT CHARSET=utf8;

INSERT INTO `group` (`group_id`, `community_id`, `name`) VALUES (0, 0, 'Anonymous');

CREATE TABLE IF NOT EXISTS `item` (
    `item_id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(250) NOT NULL,
    `date` timestamp NOT NULL,
    `description` varchar(20) NOT NULL,
    `type` int(11) NOT NULL,
    `thumbnail` varchar(255) NOT NULL,
    `view` bigint(20) NOT NULL DEFAULT '0',
    `download` bigint(20) NOT NULL DEFAULT '0',
    `sizebytes` BIGINT( 20 ) NOT NULL DEFAULT '0',
    PRIMARY KEY (`item_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `itempolicygroup` (
    `item_id` bigint(20) NOT NULL,
    `group_id` bigint(20) NOT NULL,
    `policy` tinyint(4) NOT NULL,
    `date` timestamp NULL DEFAULT CURRENT_TIMESTAMP(),
    UNIQUE KEY `item_id` (`item_id`, `group_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `itempolicyuser` (
    `item_id` bigint(20) NOT NULL,
    `user_id` bigint(20) NOT NULL,
    `policy` tinyint(4) NOT NULL,
    `date` timestamp NULL DEFAULT CURRENT_TIMESTAMP(),
    UNIQUE KEY `item_id` (`item_id`, `user_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `item2folder` (
    `item_id` bigint(20) NOT NULL,
    `folder_id` bigint(20) NOT NULL
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `item2keyword` (
    `item_id` bigint(20) NOT NULL,
    `keyword_id` bigint(20) NOT NULL,
    UNIQUE KEY `item_id` (`item_id`, `keyword_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `itemrevision` (
    `itemrevision_id` int(11) NOT NULL AUTO_INCREMENT,
    `item_id` bigint(20) NOT NULL,
    `revision` int(11) NOT NULL,
    `date` timestamp NOT NULL,
    `changes` text NOT NULL,
    `user_id` int(11) NOT NULL,
    PRIMARY KEY (`itemrevision_id`),
    UNIQUE KEY `item_id` (`item_id`, `revision`),
    KEY `user_id` (`user_id`),
    KEY `date` (`date`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `itemkeyword` (
    `keyword_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `value` varchar(255) NOT NULL,
    `relevance` int(11) NOT NULL,
    PRIMARY KEY (`keyword_id`),
    UNIQUE KEY `value` (`value`),
    KEY `relevance` (`relevance`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `metadata` (
    `metadata_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `metadatatype_id` int(11) NOT NULL,
    `element` varchar(255) NOT NULL,
    `qualifier` varchar(255) NOT NULL,
    `description` varchar(512) NOT NULL,
    PRIMARY KEY (`metadata_id`),
    KEY `metadatatype_id` (`metadatatype_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `metadatadocumentvalue` (
    `metadata_id` bigint(20) NOT NULL,
    `itemrevision_id` bigint(20) NOT NULL,
    `value` varchar(1024) NOT NULL,
    KEY `metadata_id` (`metadata_id`),
    KEY `itemrevision_id` (`itemrevision_id`),
    KEY `value` (`value`(1000))
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `metadatatype` (
    `metadatatype_id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    PRIMARY KEY (`metadatatype_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `metadatavalue` (
    `metadata_id` bigint(20) NOT NULL,
    `itemrevision_id` bigint(20) NOT NULL,
    `value` varchar(1024) NOT NULL,
    KEY `metadata_id` (`metadata_id`, `itemrevision_id`),
    KEY `value` (`value`(1000))
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `user` (
    `user_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `password` varchar(100) NOT NULL,
    `firstname` varchar(255) NOT NULL,
    `company` varchar(255),
    `thumbnail` varchar(255),
    `lastname` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `privacy` tinyint NOT NULL DEFAULT 0,
    `admin` tinyint NOT NULL DEFAULT 0,
    `folder_id` bigint(20),
    `creation` timestamp NULL DEFAULT NULL,
    `publicfolder_id` bigint(20),
    `privatefolder_id` bigint(20),
    `view` bigint(20) NOT NULL DEFAULT '0',
    PRIMARY KEY (`user_id`),
    KEY `email` (`email`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `user2group` (
    `user_id` bigint(20) NOT NULL,
    `group_id` bigint(20) NOT NULL,
    UNIQUE KEY `user_id` (`user_id`, `group_id`)
) DEFAULT CHARSET=utf8;
