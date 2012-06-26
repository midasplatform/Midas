DROP TABLE IF EXISTS mfa_otpdevice;

CREATE TABLE mfa_otpdevice (
  otpdevice_id serial PRIMARY KEY,
  user_id bigint NOT NULL,
  secret character varying(256) NOT NULL,
  algorithm character varying(256) NOT NULL,
  counter character varying(256) NOT NULL DEFAULT '',
  length integer NOT NULL
);
