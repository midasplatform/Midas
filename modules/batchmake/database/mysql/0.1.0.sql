CREATE TABLE batchmake_task (
    batchmake_task_id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    PRIMARY KEY (batchmake_task_id)
);


CREATE TABLE batchmake_itemmetric (
    itemmetric_id bigint(20) NOT NULL AUTO_INCREMENT,
    metric_name character varying(64) NOT NULL,
    bms_name character varying(256) NOT NULL,
    PRIMARY KEY (itemmetric_id)
);