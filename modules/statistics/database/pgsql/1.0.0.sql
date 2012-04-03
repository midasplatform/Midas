
CREATE TABLE statistics_download (
  download_id serial PRIMARY KEY,
  item_id bigint NOT NULL,
  user_id bigint,
  date timestamp without time zone,
  user_agent text,
  ip_location_id bigint
);
CREATE INDEX statistics_download_idx_item ON statistics_download (item_id);

CREATE TABLE statistics_ip_location (
  ip_location_id serial PRIMARY KEY,
  ip character varying(50),
  latitude character varying(50),
  longitude character varying(50)
);
CREATE INDEX statistics_ip_location_idx_ip ON statistics_ip_location (ip);
CREATE INDEX statistics_ip_location_idx_latitude ON statistics_ip_location (latitude);
