CREATE TABLE IF NOT EXISTS thumbnailcreator_itemthumbnail (
  `itemthumbnail_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `item_id` bigint(20),
  `thumbnail` varchar(255),
  INDEX(`item_id`),
  PRIMARY KEY (`itemthumbnail_id`)
);
