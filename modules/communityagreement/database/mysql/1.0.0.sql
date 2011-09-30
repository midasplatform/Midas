# DROP TABLE IF EXISTS communityagreement_agreement;
--
-- Structure of table `communityagreement_agreement`
--
CREATE TABLE IF NOT EXISTS communityagreement_agreement (
  `agreement_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `community_id` bigint(20) NOT NULL REFERENCES community.community_id,
  `agreement` text NOT NULL,
  PRIMARY KEY (`agreement_id`),
  KEY `community_id` (`community_id`)
) AUTO_INCREMENT=200;