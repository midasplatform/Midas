CREATE DEFINER=`root`@`%` PROCEDURE `create_submissions`()
BEGIN
  DECLARE finished INTEGER DEFAULT 0;
  DECLARE cur_id bigint(20) default -1;
  DECLARE cur_time timestamp default NOW();

  DECLARE curs CURSOR FOR SELECT scalar_id, submit_time FROM tracker_scalar
    WHERE submission_id=-1 group by submit_time;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET finished = 1;
    
  OPEN curs;
    
  get_scalars: LOOP
    FETCH curs INTO cur_id, cur_time;
    IF finished = 1 THEN
      LEAVE get_scalars;
    END IF;

    INSERT INTO tracker_submission (uuid, submit_time) VALUES (UUID(), cur_time);
    UPDATE tracker_scalar set submission_id=LAST_INSERT_ID() where submit_time=cur_time;
  END LOOP get_scalars;
  CLOSE curs;
END
