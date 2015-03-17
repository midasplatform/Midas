-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- MySQL database for the mfa module, version 1.0.0

CREATE TABLE IF NOT EXISTS `mfa_otpdevice` (
    `otpdevice_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) NOT NULL,
    `secret` varchar(255) NOT NULL,
    `algorithm` varchar(255) NOT NULL,
    `counter` varchar(255) NOT NULL DEFAULT '',
    `length` int(11) NOT NULL,
    PRIMARY KEY (`otpdevice_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `mfa_apitoken` (
    `apitoken_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) NOT NULL,
    `token_id` bigint(20) NOT NULL,
    `creation_date` timestamp NOT NULL,
    PRIMARY KEY (`apitoken_id`)
) DEFAULT CHARSET=utf8;
