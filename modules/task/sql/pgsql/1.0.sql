

CREATE TABLE  module_task_task (
  task_id serial  PRIMARY KEY,
  type smallint NOT NULL,
  resource_type smallint NOT NULL,
  resource_id bigint NOT NULL,
  parameters character varying(512) NOT NULL
) ;
