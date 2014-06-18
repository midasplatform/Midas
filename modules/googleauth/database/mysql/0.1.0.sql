CREATE TABLE IF NOT EXISTS `googleauth_user` (
  `googleauth_user_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  PRIMARY KEY (`googleauth_user_id`),
  KEY `user_id` (`user_id`)
);
