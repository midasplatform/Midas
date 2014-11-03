-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- MySQL database for the comments module, version 1.0.0

CREATE TABLE IF NOT EXISTS `comments_item` (
    `comment_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `item_id` bigint(20) NOT NULL,
    `user_id` bigint(20) NOT NULL,
    `comment` text NOT NULL,
    `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`comment_id`),
    KEY (`item_id`),
    KEY (`user_id`)
) DEFAULT CHARSET=utf8;
