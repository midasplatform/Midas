CREATE TABLE googleauth_user (
  googleauth_user_id serial PRIMARY KEY,
  user_id bigint NOT NULL
);

CREATE INDEX googleauth_user_user_id_idx ON googleauth_user (user_id);
