CREATE TABLE IF NOT EXISTS `packages_extension` (
  `extension_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `item_id` bigint(20) NOT NULL,
  `application_id` bigint(20) NOT NULL,
  `os` varchar(255) NOT NULL,
  `arch` varchar(255) NOT NULL,
  `repository_url` varchar(255) NOT NULL,
  `revision` varchar(255) NOT NULL,
  `submissiontype` varchar(255) NOT NULL,
  `packagetype` varchar(255) NOT NULL,
  `application_revision` varchar(255) NOT NULL,
  `release` varchar(255) NOT NULL,
  `icon_url` text NOT NULL,
  `productname` varchar(255) NOT NULL,
  `codebase` varchar(255) NOT NULL,
  `development_status` text NOT NULL,
  `category` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `homepage` text NOT NULL,
  `repository_type` varchar(10) NOT NULL DEFAULT '',
  `screenshots` text NOT NULL,
  `contributors` text NOT NULL,
  PRIMARY KEY (`extension_id`),
  KEY `release` (`release`),
  KEY `category` (`category`),
  KEY `application_id` (`application_id`)
);


CREATE TABLE IF NOT EXISTS `packages_extensioncompatibility` (
  `extension_id` bigint(20) NOT NULL,
  `core_revision` varchar(255) NOT NULL
);


CREATE TABLE IF NOT EXISTS `packages_extensiondependency` (
  `extension_name` varchar(255) NOT NULL,
  `extension_dependency` varchar(255) NOT NULL
);


CREATE TABLE IF NOT EXISTS `packages_package` (
  `package_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `item_id` bigint(20) NOT NULL,
  `application_id` bigint(20) NOT NULL,
  `os` varchar(256) NOT NULL,
  `arch` varchar(256) NOT NULL,
  `revision` varchar(256) NOT NULL,
  `submissiontype` varchar(256) NOT NULL,
  `packagetype` varchar(256) NOT NULL,
  `productname` varchar(255) NOT NULL DEFAULT '',
  `codebase` varchar(255) NOT NULL DEFAULT '',
  `checkoutdate` timestamp NULL DEFAULT NULL,
  `release` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`package_id`),
  KEY `release` (`release`),
  KEY `application_id` (`application_id`)
);


CREATE TABLE IF NOT EXISTS `packages_project` (
  `project_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `community_id` bigint(20) NOT NULL,
  PRIMARY KEY (`project_id`)
);


CREATE TABLE IF NOT EXISTS `packages_application` (
  `application_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL DEFAULT '',
  PRIMARY KEY (`application_id`),
  KEY `project_id` (`project_id`)
);
