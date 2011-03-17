
CREATE TABLE IF NOT EXISTS `module_task` (
  `task_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `type` tinyint(4) NOT NULL,
  `resource_type` tinyint(4) NOT NULL,
  `resource_id` bigint(20) NOT NULL,
  `parameters` varchar(512) NOT NULL,
  PRIMARY KEY (`task_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=10 ;


