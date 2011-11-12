CREATE TABLE IF NOT EXISTS batchmake_task (
    batchmake_task_id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    work_dir text,
    PRIMARY KEY (batchmake_task_id)
)   DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS batchmake_itemmetric (
    itemmetric_id bigint(20) NOT NULL AUTO_INCREMENT,
    metric_name character varying(64) NOT NULL,
    bms_name character varying(256) NOT NULL,
    PRIMARY KEY (itemmetric_id)
)   DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS condor_dag (
    condor_dag_id bigint(20) NOT NULL AUTO_INCREMENT,
    batchmake_task_id bigint(20) NOT NULL,
    log_filename text NOT NULL,
    PRIMARY KEY (condor_dag_id)
)   DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS condor_job (
    condor_job_id bigint(20) NOT NULL AUTO_INCREMENT,
    condor_dag_id bigint(20) NOT NULL,
    jobdefinition_filename text NOT NULL,
    output_filename text NOT NULL,
    error_filename text NOT NULL,
    log_filename text NOT NULL,
    PRIMARY KEY (condor_job_id)
)   DEFAULT CHARSET=utf8;