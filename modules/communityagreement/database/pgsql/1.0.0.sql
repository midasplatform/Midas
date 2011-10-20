# DROP TABLE IF EXISTS communityagreement_agreement;
--
-- Structure of table `communityagreement_agreement`
--
CREATE TABLE communityagreement_agreement (
  agreement_id serial PRIMARY KEY,
  community_id bigint NOT NULL,
  agreement text NOT NULL,
)