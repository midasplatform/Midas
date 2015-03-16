-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- MySQL database for the example module, version 1.0.0

CREATE TABLE IF NOT EXISTS `example_wallet` (
    `example_wallet_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) NOT NULL,
    `dollars` bigint(20) NOT NULL,
    PRIMARY KEY (`example_wallet_id`)
) DEFAULT CHARSET=utf8;
