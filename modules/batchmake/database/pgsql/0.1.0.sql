CREATE TABLE batchmake_task (
    batchmake_task_id serial PRIMARY KEY,
    user_id bigint NOT NULL,
    work_dir text
);


CREATE TABLE batchmake_itemmetric (
    itemmetric_id serial PRIMARY KEY,
    metric_name character varying(64) NOT NULL,
    bms_name character varying(256) NOT NULL
);

CREATE TABLE condor_dag (
    condor_dag_id serial PRIMARY KEY,
    batchmake_task_id bigint NOT NULL,
    log_filename text NOT NULL
);

CREATE TABLE condor_job (
    condor_job_id serial PRIMARY KEY,
    condor_dag_id bigint NOT NULL,
    jobdefinition_filename text NOT NULL,
    output_filename text NOT NULL,
    error_filename text NOT NULL,
    log_filename text NOT NULL
);