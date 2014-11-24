-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- MySQL database for the ldap module, version 1.1.0

CREATE TABLE IF NOT EXISTS `ldap_user` (
    `ldap_user_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) NOT NULL,
    `login` varchar(255) NOT NULL,
    PRIMARY KEY (`ldap_user_id`),
    KEY (`login`)
) DEFAULT CHARSET=utf8;
