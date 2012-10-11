CREATE TABLE tracker_producer (
  producer_id bigint serial PRIMARY KEY,
  community_id bigint NOT NULL,
  repository character varying(255) NOT NULL,
  executable_name character varying(255) NOT NULL,
  display_name character varying(255) NOT NULL,
  description text NOT NULL
);
CREATE INDEX tracker_producer_community_id ON tracker_producer (community_id);

CREATE TABLE tracker_trend (
  trend_id bigint serial PRIMARY KEY,
  producer_id bigint NOT NULL,
  metric_name character varying(255) NOT NULL,
  display_name character varying(255) NOT NULL,
  unit character varying(255) NOT NULL,
  config_item_id bigint,
  test_dataset_id bigint,
  truth_dataset_id bigint
);
CREATE INDEX tracker_trend_producer_id ON tracker_trend (producer_id);

CREATE TABLE tracker_scalar (
  scalar_id bigint serial PRIMARY KEY,
  trend_id bigint NOT NULL,
  value double precision,
  producer_revision character varying(255),
  submit_time timestamp without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX tracker_scalar_trend_id ON tracker_scalar (trend_id);
CREATE INDEX tracker_scalar_submit_time ON tracker_scalar (submit_time);

CREATE TABLE tracker_scalar2item (
  scalar_id bigint NOT NULL,
  item_id bigint NOT NULL,
  label character varying(255) NOT NULL
);
CREATE INDEX tracker_scalar2item_scalar_id ON tracker_scalar2item (scalar_id);

CREATE TABLE tracker_threshold (
  threshold_id serial PRIMARY KEY,
  trend_id bigint NOT NULL,
  value double precision,
  comparison character varying(2)
);
CREATE INDEX tracker_threshold_trend_id ON tracker_threshold (trend_id);

CREATE TABLE IF NOT EXISTS tracker_threshold_notification (
  threshold_notification_id serial PRIMARY KEY,
  threshold_id bigint NOT NULL,
  action character varying(80) NOT NULL,
  recipient_id bigint NOT NULL
);
CREATE INDEX tracker_threshold_notification_threshold_id ON tracker_threshold_notification (threshold_id);
