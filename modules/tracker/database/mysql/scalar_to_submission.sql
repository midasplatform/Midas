CREATE DEFINER=`root`@`%` PROCEDURE `scalar_to_submission`()
BEGIN

  DECLARE finished INTEGER DEFAULT 0;
  DECLARE cur_id bigint(20) default -1;
  DECLARE cur_producer_revision varchar(255) default '';
  DECLARE cur_user_id bigint(20) default -1;
  DECLARE cur_official tinyint(4) default 1;
  DECLARE cur_build_results_url text default '';
  DECLARE cur_branch varchar(255) default '';
  DECLARE cur_extra_urls text default '';
  DECLARE cur_reproduction_command text default '';

  DECLARE curs CURSOR FOR
    SELECT ts.submission_id, ts.producer_revision, ts.user_id, ts.official,
      ts.build_results_url, ts.branch, ts.extra_urls, ts.reproduction_command
    FROM tracker_scalar AS ts, tracker_submission AS tu WHERE ts.submission_id = tu.submission_id
    GROUP BY tu.submission_id;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET finished = 1;

  OPEN curs;

  get_scalars: LOOP
    FETCH curs INTO cur_id, cur_producer_revision, cur_user_id, cur_official,
      cur_build_results_url, cur_branch, cur_extra_urls, cur_reproduction_command;
    IF finished = 1 THEN
      LEAVE get_scalars;
    END IF;

    UPDATE tracker_submission SET
      producer_revision=cur_producer_revision, user_id=cur_user_id, official=cur_official,
      build_results_url=cur_build_results_url, branch=cur_branch, extra_urls=cur_extra_urls,
      reproduction_command=cur_reproduction_command WHERE submission_id=cur_id;

  END LOOP get_scalars;

  CLOSE curs;

END
