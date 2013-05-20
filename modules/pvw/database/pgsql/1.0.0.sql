
CREATE TABLE pvw_instance (
  instance_id serial PRIMARY KEY,
  item_id bigint NOT NULL,
  port integer NOT NULL,
  pid integer NOT NULL,
  sid character varying(127) NOT NULL,
  secret character varying(64) NOT NULL,
  creation_date timestamp without time zone NOT NULL
);
