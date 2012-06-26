CREATE TABLE IF NOT EXISTS `mfa_otpdevice` (
  `otpdevice_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `secret` varchar(255) NOT NULL,
  `algorithm` varchar(255) NOT NULL,
  `counter` varchar(255) NOT NULL DEFAULT '',
  `length` int(11) NOT NULL,
  PRIMARY KEY (`otpdevice_id`)
) DEFAULT CHARSET=utf8;
