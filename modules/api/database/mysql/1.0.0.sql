

CREATE TABLE IF NOT EXISTS `api_userapi` (
  `userapi_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `apikey` varchar(40) NOT NULL,
  `application_name` varchar(256) NOT NULL,
  `token_expiration_time` int(11) NOT NULL,
  `creation_date` timestamp NULL DEFAULT NULL ,
  PRIMARY KEY (`userapi_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

CREATE TABLE api_token (
  `token_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `userapi_id` bigint(20) NOT NULL,
  `token` varchar(40) NOT NULL,
  `expiration_date` timestamp NULL DEFAULT NULL ,
  PRIMARY KEY (`token_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

