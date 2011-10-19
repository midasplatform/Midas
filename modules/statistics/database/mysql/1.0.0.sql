
CREATE TABLE IF NOT EXISTS `statistics_download` (
  `download_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `item_id` bigint(20) NOT NULL,
  `user_id` bigint(20),
  `ip` VARCHAR(50) NOT NULL,
  `latitude` VARCHAR(50) ,
  `longitude` VARCHAR(50) ,
  `date`  timestamp,
  PRIMARY KEY (`download_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;
