CREATE TABLE batchmake_task (
    batchmake_task_id serial PRIMARY KEY,
    user_id bigint NOT NULL
);


CREATE TABLE batchmake_itemmetric (
    itemmetric_id serial PRIMARY KEY,
    metric_name character varying(64) NOT NULL,
    bms_name character varying(256) NOT NULL
);