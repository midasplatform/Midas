
-- `communityagreement_agreement` table
ALTER TABLE `community` ENGINE = InnoDB;

DROP TABLE IF EXISTS communityagreement_agreement;
CREATE TABLE communityagreement_agreement (
  `agreement_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `community_id` bigint(20) NOT NULL REFERENCES community.community_id,
  `agreement` text NOT NULL,
  PRIMARY KEY (`agreement_id`),
  KEY `community_id` (`community_id`)
) ENGINE=InnoDB AUTO_INCREMENT=200;
