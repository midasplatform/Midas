CREATE TABLE IF NOT EXISTS `googleauth_user` (
  `googleauth_user_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `google_person_id` varchar(255) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  PRIMARY KEY (`googleauth_user_id`),
  KEY `user_id` (`user_id`),
  KEY `google_person_id` (`google_person_id`)
);
