-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- MySQL database for the communityagreement module, version 1.0.0

CREATE TABLE IF NOT EXISTS `communityagreement_agreement` (
    `agreement_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `community_id` bigint(20) NOT NULL REFERENCES `community` (`community_id`),
    `agreement` text NOT NULL,
    PRIMARY KEY (`agreement_id`),
    KEY (`community_id`)
) DEFAULT CHARSET=utf8;
