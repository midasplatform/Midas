CREATE TABLE googleauth_user (
  googleauth_user_id serial PRIMARY KEY,
  google_person_id character varying(255) NOT NULL,
  user_id bigint NOT NULL
);

CREATE INDEX googleauth_user_user_id_idx ON googleauth_user (user_id);
CREATE INDEX googleauth_user_gperson_id_idx ON googleauth_user (google_person_id);
