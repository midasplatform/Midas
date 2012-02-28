
CREATE TABLE IF NOT EXISTS `comments_item` (
  `comment_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `item_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `comment` text NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`comment_id`),
  INDEX (`item_id`),
  INDEX (`user_id`)
) DEFAULT CHARSET=utf8;
