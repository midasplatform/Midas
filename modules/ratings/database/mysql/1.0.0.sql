-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- MySQL database for the ratings module, version 1.0.0

CREATE TABLE IF NOT EXISTS `ratings_item` (
    `rating_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `item_id` bigint(20) NOT NULL,
    `user_id` bigint(20) NOT NULL,
    `rating` tinyint(2) NOT NULL,
    PRIMARY KEY (`rating_id`),
    KEY (`item_id`),
    KEY (`user_id`)
) DEFAULT CHARSET=utf8;
