-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- MySQL database for the googleauth module, version 1.1.0

CREATE TABLE IF NOT EXISTS `googleauth_user` (
    `googleauth_user_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `google_person_id` varchar(255) NOT NULL,
    `user_id` bigint(20) NOT NULL,
    PRIMARY KEY (`googleauth_user_id`),
    KEY `google_person_id` (`google_person_id`),
    KEY `user_id` (`user_id`)
);
