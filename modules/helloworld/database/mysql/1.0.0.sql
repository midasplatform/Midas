
CREATE TABLE IF NOT EXISTS `helloworld_hello` (
  `hello_id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`hello_id`)
) DEFAULT CHARSET=utf8;

INSERT INTO `helloworld_hello` (`hello_id`) VALUES (default(`hello_id`));
