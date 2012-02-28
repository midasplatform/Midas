
CREATE TABLE IF NOT EXISTS `ratings_item` (
  `rating_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `item_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `rating` tinyint(2) NOT NULL,
  PRIMARY KEY (`rating_id`),
  INDEX (`item_id`),
  INDEX (`user_id`)
) DEFAULT CHARSET=utf8;
