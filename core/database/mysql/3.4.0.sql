-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- MySQL core database, version 3.4.0

CREATE TABLE IF NOT EXISTS `activedownload` (
    `activedownload_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `ip` varchar(100) NOT NULL DEFAULT '',
    `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_update` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY (`activedownload_id`),
    KEY (`ip`)
) DEFAULT CHARSET=utf8;

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
    `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`bitstream_id`),
    KEY (`itemrevision_id`),
    KEY (`checksum`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `community` (
    `community_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `creation` timestamp,
    `privacy` tinyint(4) NOT NULL,
    `folder_id` bigint(20),
    `admingroup_id` bigint(20),
    `moderatorgroup_id` bigint(20),
    `membergroup_id` bigint(20) NOT NULL DEFAULT '0',
    `view` bigint(20) NOT NULL DEFAULT '0',
    `can_join` int(11) DEFAULT '0',
    `uuid` varchar(255) DEFAULT '',
    PRIMARY KEY (`community_id`),
    KEY (`name`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `communityinvitation` (
    `communityinvitation_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `community_id` bigint(20),
    `user_id` bigint(20),
    `group_id` bigint(20),
    PRIMARY KEY (`communityinvitation_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `errorlog` (
    `errorlog_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `priority` tinyint(4) NOT NULL,
    `module` varchar(255) NOT NULL,
    `message` text NOT NULL,
    `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`errorlog_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `feed` (
    `feed_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `user_id` bigint(20) NOT NULL,
    `type` tinyint(4) NOT NULL,
    `resource` varchar(255) NOT NULL,
    PRIMARY KEY (`feed_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `feed2community` (
    `feed_id` bigint(20) NOT NULL,
    `community_id` bigint(20) NOT NULL,
    UNIQUE KEY `feed_community_id` (`feed_id`, `community_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `feedpolicygroup` (
    `feed_id` bigint(20) NOT NULL,
    `group_id` bigint(20) NOT NULL,
    `policy` tinyint(4) NOT NULL,
    `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `feed_group_id` (`feed_id`, `group_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `feedpolicyuser` (
    `feed_id` bigint(20) NOT NULL,
    `user_id` bigint(20) NOT NULL,
    `policy` tinyint(4) NOT NULL,
    `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `feed_user_id` (`feed_id`, `user_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `folder` (
    `folder_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `left_index` bigint(20) NOT NULL,
    `right_index` bigint(20) NOT NULL,
    `parent_id` bigint(20) NOT NULL DEFAULT '0',
    `name` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `date_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `view` bigint(20) NOT NULL DEFAULT '0',
    `teaser` varchar(255) DEFAULT '',
    `privacy_status` int(11) DEFAULT '0',
    `uuid` varchar(255) DEFAULT '',
    `date_creation` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY (`folder_id`),
    KEY (`parent_id`),
    KEY (`left_index`),
    KEY (`right_index`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `folderpolicygroup` (
    `folder_id` bigint(20) NOT NULL,
    `group_id` bigint(20) NOT NULL,
    `policy` tinyint(4) NOT NULL,
    `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `folder_group_id` (`folder_id`, `group_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `folderpolicyuser` (
    `folder_id` bigint(20) NOT NULL,
    `user_id` bigint(20) NOT NULL,
    `policy` tinyint(4) NOT NULL,
    `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `folder_user_id` (`folder_id`, `user_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `group` (
    `group_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `community_id` bigint(20) NOT NULL,
    `name` varchar(255) NOT NULL,
    PRIMARY KEY (`group_id`),
    KEY (`community_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `item` (
    `item_id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `date_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `description` text NOT NULL,
    `type` int(11) NOT NULL,
    `view` bigint(20) NOT NULL DEFAULT '0',
    `download` bigint(20) NOT NULL DEFAULT '0',
    `sizebytes` bigint(20) NOT NULL DEFAULT '0',
    `privacy_status` int(11) DEFAULT '0',
    `uuid` varchar(255) DEFAULT '',
    `date_creation` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    `thumbnail_id` bigint(20),
    PRIMARY KEY (`item_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `item2folder` (
    `item_id` bigint(20) NOT NULL,
    `folder_id` bigint(20) NOT NULL,
    KEY (`folder_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `itempolicygroup` (
    `item_id` bigint(20) NOT NULL,
    `group_id` bigint(20) NOT NULL,
    `policy` tinyint(4) NOT NULL,
    `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `item_group_id` (`item_id`, `group_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `itempolicyuser` (
    `item_id` bigint(20) NOT NULL,
    `user_id` bigint(20) NOT NULL,
    `policy` tinyint(4) NOT NULL,
    `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `item_user_id` (`item_id`, `user_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `itemrevision` (
    `itemrevision_id` int(11) NOT NULL AUTO_INCREMENT,
    `item_id` bigint(20) NOT NULL,
    `revision` int(11) NOT NULL,
    `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `changes` text NOT NULL,
    `user_id` int(11) NOT NULL,
    `uuid` varchar(255) DEFAULT '',
    `license_id` bigint(20),
    PRIMARY KEY (`itemrevision_id`),
    UNIQUE KEY `item_revision_id` (`item_id`, `revision`),
    KEY (`user_id`),
    KEY (`date`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `license` (
    `license_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `name` text NOT NULL,
    `fulltext` text NOT NULL,
    PRIMARY KEY (`license_id`)
) DEFAULT CHARSET=utf8;

INSERT INTO `license` VALUES
('1', 'Public (PDDL)', '**You are free:**\n\n* To Share: To copy, distribute and use the database.\n* To Create: To produce works from the database.\n* To Adapt: To modify, transform, and build upon the database.\n\n[Full License Information](http://opendatacommons.org/licenses/pddl/summary)'),
('2', 'Public: Attribution (ODC-BY)', '**You are free:**\n\n* To Share: To copy, distribute and use the database.\n* To Create: To produce works from the database.\n* To Adapt: To modify, transform, and build upon the database.\n\n**As long as you:**\n\n* Attribute: You must attribute any public use of the database, or works produced from the database, in the manner specified in the license. For any use or redistribution of the database, or works produced from it, you must make clear to others the license of the database and keep intact any notices on the original database.\n\n[Full License Information](http://opendatacommons.org/licenses/by/summary)'),
('3', 'Public: Attribution, Share-Alike (ODbL)', '**You are free:**\n\n* To Share: To copy, distribute and use the database.\n* To Create: To produce works from the database.\n* To Adapt: To modify, transform, and build upon the database.\n\n**As long as you:**\n\n* Attribute: You must attribute any public use of the database, or works produced from the database, in the manner specified in the license. For any use or redistribution of the database, or works produced from it, you must make clear to others the license of the database and keep intact any notices on the original database.\n* Share-Alike: If you publicly use any adapted version of this database, or works produced from an adapted database, you must also offer that adapted database under the ODbL.\n* Keep open: If you redistribute the database, or an adapted version of it, then you may use technological measures that restrict the work (such as DRM) as long as you also redistribute a version without such measures.\n\n[Full License Information](http://opendatacommons.org/licenses/odbl/summary)'),
('4', 'Private: All Rights Reserved', 'This work is copyrighted by its author or licensor. You must not share, distribute, or modify this work without the prior consent of the author or licensor.'),
('5', 'Public: Attribution (CC BY 3.0)', '**You are free:**\n\n* To Share: To copy, distribute and transmit the work.\n* To Remix: To adapt the work.\n* To make commercial use of the work.\n\n**Under the following conditions:**\n\n* Attribution: You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).\n\n[Full License Information](http://creativecommons.org/licenses/by/3.0/)'),
('6', 'Public: Attribution, Share-Alike (CC BY-SA 3.0)', '**You are free:**\n\n* To Share: To copy, distribute and transmit the work.\n* To Remix: To adapt the work.\n* To make commercial use of the work.\n\n**Under the following conditions:**\n\n* Attribution: You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).\n* Share-Alike: If you alter, transform, or build upon this work, you may distribute the resulting work only under the same or similar license to this one.\n\n[Full License Information](http://creativecommons.org/licenses/by-sa/3.0/)'),
('7', 'Public: Attribution, No Derivative Works (CC BY-ND 3.0)', '**You are free:**\n\n* To Share: To copy, distribute and transmit the work.\n* To make commercial use of the work.\n\n**Under the following conditions:**\n\n* Attribution: You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).\n* No Derivative Works: You may not alter, transform, or build upon this work.\n\n[Full License Information](http://creativecommons.org/licenses/by-nd/3.0/)'),
('8', 'Public: Attribution, Non-Commercial (CC BY-NC 3.0)', '**You are free:**\n\n* To Share: To copy, distribute and transmit the work.\n* To Remix: To adapt the work.\n\n**Under the following conditions:**\n\n* Attribution: You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).\n* Non-Commercial: You may not use this work for commercial purposes.\n\n[Full License Information](http://creativecommons.org/licenses/by-nc/3.0/)'),
('9', 'Public: Attribution, Non-Commercial, Share-Alike (CC BY-NC-SA 3.0)', '**You are free:**\n\n* To Share: To copy, distribute and transmit the work.\n* To Remix: To adapt the work.\n\n**Under the following conditions:**\n\n* Attribution: You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).\n* Non-Commercial: You may not use this work for commercial purposes.\n* Share-Alike: If you alter, transform, or build upon this work, you may distribute the resulting work only under the same or similar license to this one.\n\n[Full License Information](http://creativecommons.org/licenses/by-nc-sa/3.0/)'),
('10', 'Public: Attribution, Non-Commercial, No Derivative Works (CC BY-NC-ND 3.0)', '**You are free:**\n\n* To Share: To copy, distribute and transmit the work.\n\n**Under the following conditions:**\n\n* Attribution: You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).\n* Non-Commercial: You may not use this work for commercial purposes.\n* No Derivative Works: You may not alter, transform, or build upon this work.\n\n[Full License Information](http://creativecommons.org/licenses/by-nc-nd/3.0/)');

CREATE TABLE IF NOT EXISTS `metadata` (
    `metadata_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `metadatatype` int(11) DEFAULT '0',
    `element` varchar(255) NOT NULL,
    `qualifier` varchar(255) NOT NULL,
    PRIMARY KEY (`metadata_id`),
    KEY (`metadatatype`)
) DEFAULT CHARSET=utf8;

INSERT INTO `metadata` VALUES
('1', '0', 'contributor', 'author'),
('2', '0', 'date', 'uploaded'),
('3', '0', 'date', 'issued'),
('4', '0', 'date', 'created'),
('5', '0', 'identifier', 'citation'),
('6', '0', 'identifier', 'uri'),
('7', '0', 'identifier', 'pubmed'),
('8', '0', 'identifier', 'doi'),
('9', '0', 'description', 'general'),
('10', '0', 'description', 'provenance'),
('11', '0', 'description', 'sponsorship'),
('12', '0', 'description', 'publisher'),
('13', '0', 'subject', 'keyword'),
('14', '0', 'subject', 'ocis');

CREATE TABLE IF NOT EXISTS `metadatavalue` (
    `metadatavalue_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `metadata_id` bigint(20) NOT NULL,
    `itemrevision_id` bigint(20) NOT NULL,
    `value` varchar(1024) NOT NULL,
    PRIMARY KEY (`metadatavalue_id`),
    KEY `metadata_itemrevision_id` (`metadata_id`, `itemrevision_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `newuserinvitation` (
    `newuserinvitation_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `auth_key` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `inviter_id` bigint(20) NOT NULL,
    `community_id` bigint(20) NOT NULL,
    `group_id` bigint(20) NOT NULL,
    `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`newuserinvitation_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `password` (
    `hash` varchar(128) NOT NULL,
    PRIMARY KEY (`hash`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `pendinguser` (
    `pendinguser_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `auth_key` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `firstname` varchar(255) NOT NULL,
    `lastname` varchar(255) NOT NULL,
    `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `salt` varchar(64) NOT NULL DEFAULT '',
    PRIMARY KEY (`pendinguser_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `progress` (
    `progress_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `message` text NOT NULL,
    `current` bigint(20) NOT NULL,
    `maximum` bigint(20) NOT NULL,
    `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_update` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY (`progress_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `setting` (
    `setting_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `module` varchar(255) NOT NULL,
    `name` varchar(255) NOT NULL,
    `value` text,
    PRIMARY KEY (`setting_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `token` (
    `token_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `userapi_id` bigint(20) NOT NULL,
    `token` varchar(64) NOT NULL,
    `expiration_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`token_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `user` (
    `user_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `firstname` varchar(255) NOT NULL,
    `company` varchar(255),
    `thumbnail` varchar(255),
    `lastname` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `privacy` tinyint(4) NOT NULL DEFAULT '0',
    `admin` tinyint(4) NOT NULL DEFAULT '0',
    `folder_id` bigint(20),
    `creation` timestamp,
    `view` bigint(20) NOT NULL DEFAULT '0',
    `uuid` varchar(255) DEFAULT '',
    `city` varchar(100) DEFAULT '',
    `country` varchar(100) DEFAULT '',
    `website` varchar(255) DEFAULT '',
    `biography` text,
    `dynamichelp` tinyint(4) DEFAULT '1',
    `hash_alg` varchar(32) NOT NULL DEFAULT '',
    `salt` varchar(64) NOT NULL DEFAULT '',
    PRIMARY KEY (`user_id`),
    KEY (`email`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `user2group` (
    `user_id` bigint(20) NOT NULL,
    `group_id` bigint(20) NOT NULL,
    UNIQUE KEY `user_group_id` (`user_id`, `group_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `userapi` (
    `userapi_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) NOT NULL,
    `apikey` varchar(64) NOT NULL,
    `application_name` varchar(255) NOT NULL,
    `token_expiration_time` int(11) NOT NULL,
    `creation_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`userapi_id`)
) DEFAULT CHARSET=utf8;
