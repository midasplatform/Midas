CREATE PROCEDURE `migrate_params`()
BEGIN

  DECLARE finished INTEGER DEFAULT 0;
  DECLARE cur_id bigint(20) default -1;
  DECLARE cur_name varchar(255) default '';
  DECLARE cur_type enum('text', 'numeric') default 'text';
  DECLARE cur_text_value text default '';
  DECLARE cur_numeric_value double default 0;

  DECLARE curs CURSOR FOR SELECT submission_id, param_name, param_type, text_value, numeric_value
    FROM tracker_param, tracker_scalar WHERE tracker_param.scalar_id=tracker_scalar.scalar_id
    GROUP BY submission_id, param_name;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET finished = 1;
    
  OPEN curs;
    
  get_params: LOOP
    FETCH curs INTO cur_id, cur_name, cur_type, cur_text_value, cur_numeric_value;
    IF finished = 1 THEN
      LEAVE get_params;
    END IF;
    INSERT INTO tracker_submissionparam (submission_id, param_name, param_type, text_value, numeric_value)
      VALUES (cur_id, cur_name, cur_type, cur_text_value, cur_numeric_value);
  END LOOP get_params;
    
  CLOSE curs;
END
