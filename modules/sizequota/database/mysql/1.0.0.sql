
CREATE TABLE IF NOT EXISTS `sizequota_folderquota` (
  `folderquota_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `folder_id` bigint(20) NOT NULL,
  `quota` varchar(50) NOT NULL,
  PRIMARY KEY (`folderquota_id`),
  KEY `folder_id` (`folder_id`)
) DEFAULT CHARSET=utf8;
