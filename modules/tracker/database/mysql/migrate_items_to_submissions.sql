CREATE DEFINER=`root`@`%` PROCEDURE `migrate_items_to_submissions`()
BEGIN

  DECLARE finished INTEGER DEFAULT 0;
  DECLARE cur_item_id bigint(20) default -1;
  DECLARE cur_submission_id bigint(20) default -1;
  DECLARE cur_label varchar(255) default '';

  DECLARE curs CURSOR FOR
    SELECT DISTINCT ti.item_id, ti.label, tu.submission_id FROM
      tracker_scalar AS ts, tracker_submission AS tu, tracker_scalar2item AS ti WHERE
      ts.submission_id=tu.submission_id AND ts.scalar_id=ti.scalar_id;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET finished = 1;
    
  OPEN curs;
    
  get_items: LOOP
    FETCH curs INTO cur_item_id, cur_label, cur_submission_id;
    IF finished = 1 THEN
      LEAVE get_items;
    END IF;
        
    INSERT INTO tracker_submission2item (submission_id, item_id, label) VALUES (cur_submission_id, cur_item_id, cur_label);
        
  END LOOP get_items;

  CLOSE curs;

END
