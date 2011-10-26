DROP TABLE IF EXISTS api_userapi;
DROP TABLE IF EXISTS api_token;

CREATE TABLE api_userapi (
  userapi_id serial PRIMARY KEY,
  user_id bigint NOT NULL,
  apikey character varying(40) NOT NULL,
  application_name character varying(256) NOT NULL,
  token_expiration_time integer NOT NULL,
  creation_date timestamp without time zone
);

CREATE TABLE api_token (
  token_id serial PRIMARY KEY,
  userapi_id bigint NOT NULL,
  token character varying(40) NOT NULL,
  expiration_date timestamp without time zone
);
